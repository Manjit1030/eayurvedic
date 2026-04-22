<?php
// app/core/algorithms.php
require_once __DIR__ . '/config.php';

// true = ON | false = OFF
$algorithm_1 = true;

// true = ON | false = OFF
$algorithm_2 = true;

// true = ON | false = OFF
$algorithm_3 = true;

/*
|--------------------------------------------------------------------------
| ALGORITHM #1 : CART TOTAL CALCULATION
|--------------------------------------------------------------------------
| Purpose:
|   Calculate final payable amount using business rules
|
| Inputs:
|   - subtotal (float)
|
| Logic (WITH algorithm):
|   - Free shipping if subtotal >= 1500
|   - 13% tax applied
|   - Discount placeholder
|
| Logic (WITHOUT algorithm):
|   - total = subtotal only
|
| Used In:
|   - checkout.php
|   - place_order.php
|
| Demo:
|   Toggle ALGO_ENABLED in config.php
|--------------------------------------------------------------------------
*/
function algo_cart_totals(float $subtotal): array
{
    global $algorithm_1;

    $subtotal = round($subtotal, 2);

    // WITHOUT algorithm
    if (!ALGO_ENABLED || !$algorithm_1) {
        return [
            'subtotal' => $subtotal,
            'shipping' => 0.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => $subtotal,
            'mode' => 'WITHOUT_ALGORITHM'
        ];
    }

    // WITH algorithm (rules)
    if ($algorithm_1) {
        $shipping = ($subtotal >= 1500) ? 0.00 : 120.00;
        $tax = round($subtotal * 0.13, 2);
        $discount = 0.00;

        $total = round($subtotal + $shipping + $tax - $discount, 2);
    }

    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'discount' => $discount,
        'total' => $total,
        'mode' => 'WITH_ALGORITHM'
    ];
}

/*
|--------------------------------------------------------------------------
| ALGORITHM #2 : SEVERITY / RISK SCORING (NEXT)
|--------------------------------------------------------------------------
| Purpose:
|   Automatically classify patient condition
|
| Inputs:
|   - symptoms text
|
| Output:
|   - mild / moderate / severe
|
| Status:
|   IMPLEMENT NEXT
|--------------------------------------------------------------------------
*/
function algo_severity_score(string $symptoms): string
{
    global $algorithm_2;

    if (!ALGO_ENABLED || !$algorithm_2) return 'mild';

    if ($algorithm_2) {
        $text = strtolower($symptoms);
        $score = 0;

        // symptom count
        $count = substr_count($text, ',') + 1;
        if ($count >= 5) $score += 3;
        elseif ($count >= 3) $score += 2;
        else $score += 1;

        // keywords
        $keywords = [
            'high fever' => 3,
            'vomiting' => 2,
            'weakness' => 2,
            'bleeding' => 3,
            'chest pain' => 3,
            'dizziness' => 2
        ];

        foreach ($keywords as $k => $v) {
            if (strpos($text, $k) !== false) {
                $score += $v;
            }
        }
    }

    if ($score >= 6) return 'severe';
    if ($score >= 4) return 'moderate';
    return 'mild';
}


/*
|--------------------------------------------------------------------------
| ALGORITHM #3 : SYMPTOM → SOLUTION MATCHING (WEIGHTED SCORING)
|--------------------------------------------------------------------------
| Purpose:
|   Suggest likely Ayurvedic care categories based on symptoms text.
|   This is decision-support for admin (NOT auto-treatment).
|
| Inputs:
|   - symptoms (string)
|   - mental_condition (string)
|   - digestive_issues (string)
|
| Output:
|   [
|     'mode' => 'WITH_ALGORITHM' | 'WITHOUT_ALGORITHM',
|     'top_categories' => [ ['category'=>'Digestive Care','score'=>6], ... ],
|     'matched_keywords' => [ 'vomiting','acidity', ... ],
|     'suggested_tags' => [ 'digestive','immunity', ... ]
|   ]
|
| Switches:
|   - ALGO_ENABLED (master)
|   - ALGO_SYMPTOM_MATCH (individual)
|--------------------------------------------------------------------------
*/
function algo_symptom_solution_match(string $symptoms, string $mental_condition = '', string $digestive_issues = ''): array
{
    global $algorithm_3;

    $symptomMatchEnabled = defined('ALGO_SYMPTOM_MATCH') ? ALGO_SYMPTOM_MATCH : true;

    if (!ALGO_ENABLED || !$symptomMatchEnabled || !$algorithm_3) {
        return [
            'mode' => 'WITHOUT_ALGORITHM',
            'top_categories' => [],
            'matched_keywords' => [],
            'suggested_tags' => []
        ];
    }

    if ($algorithm_3) {
        $text = strtolower(trim($symptoms . ' ' . $mental_condition . ' ' . $digestive_issues));
        if ($text === '') {
            return [
                'mode' => 'WITH_ALGORITHM',
                'top_categories' => [],
                'matched_keywords' => [],
                'suggested_tags' => []
            ];
        }

        // Category score map
        $scores = [
            'Digestive Care' => 0,
            'Immunity & Fever Care' => 0,
            'Respiratory Care' => 0,
            'Pain & Inflammation' => 0,
            'Stress & Sleep Care' => 0,
            'General Strength (Rasayana)' => 0,
            'Skin Care' => 0,
        ];

        // Keyword rules (keyword => [category, weight, tag])
        $rules = [
            // Digestive
            'acidity'       => ['Digestive Care', 2, 'digestive'],
            'gas'           => ['Digestive Care', 1, 'digestive'],
            'bloating'      => ['Digestive Care', 1, 'digestive'],
            'constipation'  => ['Digestive Care', 2, 'digestive'],
            'diarrhea'      => ['Digestive Care', 2, 'digestive'],
            'vomiting'      => ['Digestive Care', 3, 'digestive'],
            'nausea'        => ['Digestive Care', 2, 'digestive'],
            'indigestion'   => ['Digestive Care', 2, 'digestive'],
            'stomach pain'  => ['Digestive Care', 2, 'digestive'],

            // Immunity & Fever
            'high fever'    => ['Immunity & Fever Care', 3, 'immunity'],
            'fever'         => ['Immunity & Fever Care', 2, 'immunity'],
            'chills'        => ['Immunity & Fever Care', 2, 'immunity'],
            'cold'          => ['Immunity & Fever Care', 1, 'immunity'],
            'weakness'      => ['General Strength (Rasayana)', 2, 'rasayana'],
            'fatigue'       => ['General Strength (Rasayana)', 2, 'rasayana'],
            'loss of appetite' => ['Digestive Care', 1, 'digestive'],

            // Respiratory
            'cough'         => ['Respiratory Care', 2, 'respiratory'],
            'sore throat'   => ['Respiratory Care', 2, 'respiratory'],
            'breathing'     => ['Respiratory Care', 3, 'respiratory'],
            'asthma'        => ['Respiratory Care', 3, 'respiratory'],

            // Pain/Inflammation
            'headache'      => ['Pain & Inflammation', 2, 'pain'],
            'body pain'     => ['Pain & Inflammation', 2, 'pain'],
            'joint pain'    => ['Pain & Inflammation', 2, 'pain'],
            'swelling'      => ['Pain & Inflammation', 2, 'pain'],
            'inflammation'  => ['Pain & Inflammation', 2, 'pain'],

            // Stress/Sleep
            'stress'        => ['Stress & Sleep Care', 2, 'stress'],
            'anxiety'       => ['Stress & Sleep Care', 2, 'stress'],
            'insomnia'      => ['Stress & Sleep Care', 3, 'sleep'],
            'low sleep'     => ['Stress & Sleep Care', 2, 'sleep'],
            'restlessness'  => ['Stress & Sleep Care', 2, 'stress'],

            // Skin
            'itching'       => ['Skin Care', 2, 'skin'],
            'rash'          => ['Skin Care', 2, 'skin'],
            'acne'          => ['Skin Care', 2, 'skin'],
        ];

        $matched = [];
        $tags = [];

        // Apply rules: add weights if keyword found
        foreach ($rules as $keyword => $def) {
            [$cat, $w, $tag] = $def;
            if (strpos($text, $keyword) !== false) {
                $scores[$cat] += $w;
                $matched[] = $keyword;
                $tags[] = $tag;
            }
        }

        // Small boost for number of symptoms (comma count)
        $symCount = substr_count(strtolower($symptoms), ',') + 1;
        if ($symCount >= 5) {
            $scores['General Strength (Rasayana)'] += 1;
            $tags[] = 'rasayana';
        }

        // Convert to ranked list
        $ranked = [];
        foreach ($scores as $cat => $sc) {
            if ($sc > 0) $ranked[] = ['category' => $cat, 'score' => $sc];
        }

        usort($ranked, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Keep top 3 for UI
        $top = array_slice($ranked, 0, 3);

        // Unique tags/keywords
        $matched = array_values(array_unique($matched));
        $tags = array_values(array_unique($tags));
    }

    return [
        'mode' => 'WITH_ALGORITHM',
        'top_categories' => $top,
        'matched_keywords' => $matched,
        'suggested_tags' => $tags
    ];
}
