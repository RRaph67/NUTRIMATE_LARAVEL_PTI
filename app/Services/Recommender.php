<?php

namespace App\Services;

use App\Support\FoodCategory;
use App\Support\MealSlot;

/**
 * Mesin rekomendasi paket makan harian berbasis data nyata tabel `foods`.
 *
 * Pendekatan: untuk tiap slot makan dibangun kandidat menu (1 karbohidrat +
 * 1–2 protein + pelengkap opsional), kandidat dikompresi dengan prinsip
 * Pareto, lalu kombinasi terbaik dipilih dengan penilaian skor gizi,
 * penalti pengulangan menu, dan batas anggaran.
 */
final class Recommender
{
    /** Preferensi (chip) yang dikenali. */
    public const FOCUS_OPTIONS = ['hemat', 'protein', 'sayur', 'buah', 'seimbang'];

    /** Bobot pembagian budget per slot agar satu waktu makan tidak menghabiskan anggaran. */
    private const SLOT_BUDGET_WEIGHT = [
        MealSlot::BREAKFAST => 0.25,
        MealSlot::LUNCH => 0.35,
        MealSlot::DINNER => 0.30,
    ];

    /** Kelonggaran plafon per slot di atas bobotnya. */
    private const SLOT_BUDGET_SLACK = 1.6;

    /** Jumlah kandidat yang dievaluasi per slot. */
    private const SHORTLIST_SIZE = 14;

    /**
     * Susun paket rekomendasi.
     *
     * @param  float  $budget  Batas pengeluaran harian (rupiah).
     * @param  list<string>  $focus  Preferensi: hemat, protein, sayur, buah, seimbang.
     * @param  int  $targetCalories  Target kalori harian.
     * @param  int  $maxPackages  Jumlah paket alternatif.
     * @param  list<array>  $foods  Data makanan (id, name, category, price, calories, protein, carbs, fat).
     * @return array{packages: list<array>, meta: array}
     */
    public function recommend(
        float $budget,
        array $focus = [],
        int $targetCalories = 2000,
        int $maxPackages = 1,
        array $foods = [],
    ): array {
        $budget = max(0.0, $budget);
        $focus = array_values(array_intersect($focus, self::FOCUS_OPTIONS));

        if ($foods === []) {
            return [
                'packages' => [],
                'meta' => $this->meta($budget, $targetCalories, 0, 'Data makanan masih kosong.'),
            ];
        }

        $options = [];
        foreach (array_keys(MealSlot::all()) as $slot) {
            $candidates = $this->buildOptions($foods, $slot, $focus);

            if ($candidates === []) {
                return [
                    'packages' => [],
                    'meta' => $this->meta(
                        $budget,
                        $targetCalories,
                        0,
                        'Belum ada makanan yang cocok untuk slot '.MealSlot::label($slot).'.',
                    ),
                ];
            }

            $options[$slot] = $this->pareto($candidates);
        }

        $packages = $this->buildPackages($options, $budget, $targetCalories, $focus, $maxPackages);

        $note = '';
        if ($packages === []) {
            $cheapest = $this->cheapestTotal($options);
            $note = 'Budget Rp '.number_format($budget, 0, ',', '.')
                .' belum cukup untuk satu paket lengkap. Minimum sekitar Rp '
                .number_format($cheapest, 0, ',', '.').'.';
        }

        return [
            'packages' => $packages,
            'meta' => $this->meta($budget, $targetCalories, count($packages), $note),
        ];
    }

    /**
     * Cari beberapa paket berbeda dengan mengeluarkan opsi yang sudah terpakai.
     *
     * @param  array<string, list<array>>  $options
     * @param  list<string>  $focus
     * @return list<array>
     */
    private function buildPackages(
        array $options,
        float $budget,
        int $targetCalories,
        array $focus,
        int $maxPackages,
    ): array {
        $packages = [];
        $excluded = [];
        $attempts = 0;
        $maxAttempts = max(1, $maxPackages) * 4;

        while (count($packages) < max(1, $maxPackages) && $attempts < $maxAttempts) {
            $attempts++;

            $available = [];
            foreach ($options as $slot => $slotOptions) {
                $kept = array_values(array_filter(
                    $slotOptions,
                    fn (array $option): bool => ! isset($excluded[$slot][$option['key']]),
                ));

                if ($kept === []) {
                    return $packages;
                }

                $available[$slot] = $kept;
            }

            $picked = $this->solveWithDiversity($available, $budget, $targetCalories, $focus);
            if ($picked === null) {
                break;
            }

            $packages[] = $this->buildPackage($picked, $budget, $targetCalories, $focus);

            foreach ($picked as $slot => $option) {
                $excluded[$slot][$option['key']] = true;
            }
        }

        return $packages;
    }

    /**
     * Bangun daftar opsi untuk satu slot makan.
     *
     * @param  list<array>  $foods
     * @param  list<string>  $focus
     * @return list<array>
     */
    private function buildOptions(array $foods, string $slot, array $focus): array
    {
        $byCategory = [];
        foreach ($foods as $food) {
            $byCategory[(string) $food['category']][] = $food;
        }

        $carbs = $byCategory[FoodCategory::CARBOHYDRATE] ?? [];
        $proteins = $byCategory[FoodCategory::PROTEIN] ?? [];
        $vegetables = $byCategory[FoodCategory::VEGETABLE] ?? [];
        $fruits = in_array('buah', $focus, true) ? ($byCategory[FoodCategory::FRUIT] ?? []) : [];

        // Pelengkap opsional: tanpa pelengkap, satu sayur, satu buah, atau buah + sayur.
        $extras = [[]];
        foreach ($vegetables as $vegetable) {
            $extras[] = [$vegetable];
        }
        foreach ($fruits as $fruit) {
            $extras[] = [$fruit];
            foreach ($vegetables as $vegetable) {
                $extras[] = [$fruit, $vegetable];
            }
        }

        // Kombinasi protein: satu atau dua lauk protein berbeda.
        $proteinSets = [];
        foreach ($proteins as $i => $protein) {
            $proteinSets[] = [$protein];
            foreach ($proteins as $j => $second) {
                if ($j > $i) {
                    $proteinSets[] = [$protein, $second];
                }
            }
        }

        $options = [];
        foreach ($carbs as $carb) {
            foreach ($proteinSets as $proteinSet) {
                $base = array_merge([$carb], $proteinSet);
                foreach ($extras as $extra) {
                    $options[] = $this->makeOption(array_merge($base, $extra), $focus);
                }
            }
        }

        return $options;
    }

    /**
     * Ubah sekumpulan makanan menjadi satu opsi terhitung.
     *
     * @param  list<array>  $items
     * @param  list<string>  $focus
     */
    private function makeOption(array $items, array $focus): array
    {
        $totals = ['price' => 0.0, 'calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        $hasVegetable = false;
        $hasFruit = false;
        $ids = [];
        $names = [];

        foreach ($items as $item) {
            $totals['price'] += (float) $item['price'];
            $totals['calories'] += (float) $item['calories'];
            $totals['protein'] += (float) $item['protein'];
            $totals['carbs'] += (float) $item['carbs'];
            $totals['fat'] += (float) $item['fat'];

            $hasVegetable = $hasVegetable || $item['category'] === FoodCategory::VEGETABLE;
            $hasFruit = $hasFruit || $item['category'] === FoodCategory::FRUIT;

            $ids[] = $item['id'];
            $names[] = $item['name'];
        }

        return [
            'key' => md5(implode('|', $ids)),
            'items' => $items,
            'ids' => $ids,
            'name' => implode(' + ', $names),
            'price' => $totals['price'],
            'calories' => $totals['calories'],
            'protein' => $totals['protein'],
            'carbs' => $totals['carbs'],
            'fat' => $totals['fat'],
            'has_sayur' => $hasVegetable,
            'has_buah' => $hasFruit,
            'score' => $this->score($totals, $hasVegetable, $hasFruit, $focus),
        ];
    }

    /**
     * Skor gizi sebuah opsi; preferensi pengguna menggeser bobot dasarnya.
     *
     * @param  array{price: float, calories: float, protein: float, carbs: float, fat: float}  $totals
     * @param  list<string>  $focus
     */
    private function score(array $totals, bool $hasVegetable, bool $hasFruit, array $focus): float
    {
        $score = $totals['protein'] * 3.0 + $totals['calories'] * 0.05;

        if (in_array('protein', $focus, true)) {
            $score += $totals['protein'] * 6.0;
        }

        if (in_array('sayur', $focus, true)) {
            $score += $hasVegetable ? 20.0 : -30.0;
        }

        if (in_array('buah', $focus, true) && $hasFruit) {
            $score += 20.0;
        }

        if (in_array('seimbang', $focus, true)) {
            $energy = ($totals['protein'] * 4) + ($totals['carbs'] * 4) + ($totals['fat'] * 9);
            if ($energy > 0) {
                $deviation = abs((($totals['protein'] * 4) / $energy) - 0.20)
                    + abs((($totals['carbs'] * 4) / $energy) - 0.55)
                    + abs((($totals['fat'] * 9) / $energy) - 0.25);
                $score += max(0.0, 15.0 - ($deviation * 30.0));
            }
        }

        if (in_array('hemat', $focus, true)) {
            $score -= $totals['price'] * 0.002;
        }

        return $score;
    }

    /**
     * Kompresi Pareto: buang opsi yang lebih mahal tanpa skor lebih baik.
     *
     * @param  list<array>  $options
     * @return list<array>
     */
    private function pareto(array $options): array
    {
        usort($options, function (array $a, array $b): int {
            if ($a['price'] === $b['price']) {
                return $b['score'] <=> $a['score'];
            }

            return $a['price'] <=> $b['price'];
        });

        $result = [];
        $best = -INF;

        foreach ($options as $option) {
            if ($option['score'] > $best) {
                $result[] = $option;
                $best = $option['score'];
            }
        }

        return $result;
    }

    /**
     * Pilih kombinasi terbaik antar slot.
     *
     * DP murni cenderung memilih menu yang sama di semua slot, karena itu
     * kandidat per slot dipangkas lebih dulu lalu seluruh kombinasi dievaluasi
     * langsung agar penalti pengulangan & plafon per slot dihitung tepat.
     *
     * @param  array<string, list<array>>  $optionsBySlot
     * @param  list<string>  $focus
     */
    private function solveWithDiversity(
        array $optionsBySlot,
        float $budget,
        int $targetCalories,
        array $focus,
    ): ?array {
        $slots = array_keys($optionsBySlot);
        $shortlists = [];

        foreach ($slots as $slot) {
            $weight = self::SLOT_BUDGET_WEIGHT[$slot] ?? (1.0 / max(1, count($slots)));
            $slotCap = min($budget, $budget * $weight * self::SLOT_BUDGET_SLACK);

            $shortlists[$slot] = $this->shortlist($optionsBySlot[$slot], $slotCap);
            if ($shortlists[$slot] === []) {
                return null;
            }
        }

        $best = null;
        $bestScore = -INF;

        $walk = function (int $index, array $chosen, float $spent) use (
            &$walk,
            &$best,
            &$bestScore,
            $slots,
            $shortlists,
            $budget,
            $targetCalories,
            $focus,
        ): void {
            if ($spent > $budget) {
                return;
            }

            if ($index === count($slots)) {
                $score = $this->scoreCombination($chosen, $targetCalories, $focus);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $chosen;
                }

                return;
            }

            foreach ($shortlists[$slots[$index]] as $option) {
                $chosen[$slots[$index]] = $option;
                $walk($index + 1, $chosen, $spent + $option['price']);
            }
        };

        $walk(0, [], 0.0);

        return $best;
    }

    /**
     * Skor total satu kombinasi lengkap (semua slot).
     *
     * @param  array<string, array>  $chosen
     * @param  list<string>  $focus
     */
    private function scoreCombination(array $chosen, int $targetCalories, array $focus): float
    {
        $score = 0.0;
        $occurrences = [];
        $calories = 0.0;
        $protein = 0.0;

        foreach ($chosen as $option) {
            $score += $option['score'];
            $calories += $option['calories'];
            $protein += $option['protein'];

            foreach ($option['ids'] as $id) {
                $occurrences[$id] = ($occurrences[$id] ?? 0) + 1;
            }
        }

        // Penalti tegas untuk setiap pengulangan menu.
        foreach ($occurrences as $count) {
            if ($count > 1) {
                $score -= 500.0 * ($count - 1);
            }
        }

        // Preferensi sayur/buah bersifat wajib, bukan sekadar bonus skor.
        if (in_array('sayur', $focus, true) && ! $this->hasFlag($chosen, 'has_sayur')) {
            $score -= 5000.0;
        }
        if (in_array('buah', $focus, true) && ! $this->hasFlag($chosen, 'has_buah')) {
            $score -= 5000.0;
        }

        // Dorong pemenuhan target kalori, bukan hanya skor per item.
        if ($calories < $targetCalories) {
            $score -= ($targetCalories - $calories) * 4.0;
        } else {
            $score += min(60.0, ($calories - $targetCalories) * 0.05);
        }

        // Jaga asupan protein minimal yang wajar.
        $score += min($protein, 120.0) * 0.5;

        return $score;
    }

    /**
     * @param  array<string, array>  $chosen
     */
    private function hasFlag(array $chosen, string $flag): bool
    {
        foreach ($chosen as $option) {
            if (! empty($option[$flag])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil kandidat terbaik per slot: gabungan opsi berskor tertinggi,
     * opsi termurah, serta opsi bersayur/berbuah agar preferensi tetap
     * punya kandidat.
     *
     * @param  list<array>  $options
     * @return list<array>
     */
    private function shortlist(array $options, float $budget): array
    {
        $affordable = array_values(array_filter(
            $options,
            fn (array $option): bool => $option['price'] <= $budget,
        ));

        if ($affordable === []) {
            return [];
        }

        $byScore = $affordable;
        usort($byScore, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $byPrice = $affordable;
        usort($byPrice, fn (array $a, array $b): int => $a['price'] <=> $b['price']);

        $picked = [];
        foreach (array_slice($byScore, 0, (int) ceil(self::SHORTLIST_SIZE / 2)) as $option) {
            $picked[$option['key']] = $option;
        }

        foreach (['has_sayur', 'has_buah'] as $flag) {
            $flagged = array_values(array_filter(
                $affordable,
                fn (array $option): bool => ! empty($option[$flag]),
            ));

            if ($flagged === []) {
                continue;
            }

            usort($flagged, fn (array $a, array $b): int => $b['score'] <=> $a['score']);
            $picked[$flagged[0]['key']] = $flagged[0];
        }

        foreach ($byPrice as $option) {
            if (count($picked) >= self::SHORTLIST_SIZE) {
                break;
            }

            $picked[$option['key']] = $option;
        }

        return array_values($picked);
    }

    /**
     * Total harga termurah untuk satu paket lengkap.
     *
     * @param  array<string, list<array>>  $optionsBySlot
     */
    private function cheapestTotal(array $optionsBySlot): float
    {
        $total = 0.0;

        foreach ($optionsBySlot as $options) {
            $minimum = INF;
            foreach ($options as $option) {
                $minimum = min($minimum, $option['price']);
            }
            $total += $minimum;
        }

        return $total;
    }

    /**
     * Susun struktur paket untuk dikirim ke frontend.
     *
     * @param  array<string, array>  $picked
     * @param  list<string>  $focus
     */
    private function buildPackage(array $picked, float $budget, int $targetCalories, array $focus): array
    {
        $meals = [];
        $payload = [];
        $totals = ['price' => 0.0, 'calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];

        foreach ($picked as $slot => $option) {
            $meals[] = [
                'slot' => $slot,
                'label' => MealSlot::label($slot),
                'menu' => $option['name'],
                'price' => (int) round($option['price']),
                'calories' => (int) round($option['calories']),
                'protein' => round($option['protein'], 1),
                'items' => array_map(
                    fn (array $item): array => ['id' => $item['id'], 'name' => $item['name']],
                    $option['items'],
                ),
            ];

            foreach (array_keys($totals) as $key) {
                $totals[$key] += $option[$key];
            }

            foreach ($option['items'] as $item) {
                $payload[] = ['slot' => $slot, 'food_id' => $item['id']];
            }
        }

        return [
            'name' => 'Paket '.$this->focusLabel($focus).' — '.count($meals).' Waktu Makan',
            'description' => $this->describe($focus, $targetCalories, $totals),
            'meals' => $meals,
            'total' => (int) round($totals['price']),
            'saving' => (int) round($budget - $totals['price']),
            'budget' => (int) round($budget),
            'nutrition' => [
                'calories' => (int) round($totals['calories']),
                'protein' => round($totals['protein'], 1),
                'carbs' => round($totals['carbs'], 1),
                'fat' => round($totals['fat'], 1),
            ],
            'target_calories' => $targetCalories,
            'target_reached' => $totals['calories'] >= $targetCalories,
            'payload' => $payload,
        ];
    }

    /**
     * @param  list<string>  $focus
     */
    private function focusLabel(array $focus): string
    {
        $labels = [
            'hemat' => 'Hemat',
            'protein' => 'Tinggi Protein',
            'sayur' => 'Ada Sayur',
            'buah' => 'Ada Buah',
            'seimbang' => 'Seimbang',
        ];

        $selected = array_values(array_intersect($focus, array_keys($labels)));

        return $selected === []
            ? 'Seimbang'
            : implode(' & ', array_map(fn (string $key): string => $labels[$key], $selected));
    }

    /**
     * @param  list<string>  $focus
     * @param  array<string, float>  $totals
     */
    private function describe(array $focus, int $targetCalories, array $totals): string
    {
        $parts = [];

        if ($totals['calories'] < $targetCalories) {
            $parts[] = 'Mencapai '.number_format($totals['calories'], 0, ',', '.')
                .' kkal dari target '.number_format($targetCalories, 0, ',', '.')
                .' kkal — tambah budget atau kurangi target untuk menutup selisih.';
        } else {
            $parts[] = 'Memenuhi target '.number_format($targetCalories, 0, ',', '.').' kkal harian.';
        }

        if (in_array('hemat', $focus, true)) {
            $parts[] = 'Disusun dari menu termurah yang gizinya masih layak.';
        }
        if (in_array('protein', $focus, true)) {
            $parts[] = 'Protein diutamakan: '.round($totals['protein'], 1).' g.';
        }
        if (in_array('sayur', $focus, true)) {
            $parts[] = 'Setiap waktu makan menyertakan sayur.';
        }
        if (in_array('buah', $focus, true)) {
            $parts[] = 'Menyertakan buah segar.';
        }

        return implode(' ', $parts);
    }

    private function meta(float $budget, int $targetCalories, int $count, string $note): array
    {
        return [
            'budget' => (int) round($budget),
            'target_calories' => $targetCalories,
            'package_count' => $count,
            'note' => $note,
        ];
    }
}