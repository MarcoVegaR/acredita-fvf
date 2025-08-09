<?php
// --- Ajustes especiales cuando hay UNA sola zona ---
$single = ($n === 1);

// Márgenes más apretados para 1 zona
$boxMargin  = $single ? 8  : max(4, intval($gap / 3)); // antes  = max(4, gap/3)
$numPadding = $single ? 2  : 6;                        // antes  = 6

// Límites reales para el texto (dejamos casi todo el espacio)
$allowedTextW = max(1, ($boxW - 2 * $boxMargin) - 2 * $numPadding);
$allowedTextH = max(1, ($boxH - 2 * $boxMargin) - 2 * $numPadding);

// Tamaño de fuente uniforme
$uniformFontSize = 400;
foreach ($zoneNumbers as $zn) {
    $candidate = $this->findMaxFontSize((string) $zn, $fontPath, $allowedTextW, $allowedTextH);
    $uniformFontSize = min($uniformFontSize, $candidate);
}
// pequeño "empujón" para que visualmente llene más, solo con 1 zona
if ($single) {
    $uniformFontSize = intval($uniformFontSize * 1.06); // 6% más
}
$uniformFontSize = max(1, $uniformFontSize);
