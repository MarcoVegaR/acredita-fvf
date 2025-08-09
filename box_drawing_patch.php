<?php
// Caja AA supersample + sombra suave
// En el dibujo de la caja, usar radio/borde algo menores si es 1 zona
$radiusPx = $single
    ? intval(max(16, round($drawH * 0.16)))  // antes ~0.22
    : (isset($block['corner_radius']) ? intval($block['corner_radius']) : intval(round($drawH * 0.18)));

$strokePx = $single
    ? 5                                      // antes 6–7
    : (isset($block['border_width']) ? intval($block['border_width']) : $borderWidth);
                
$shadowBlur = 0;
$offsetAA   = 0;

$boxLayer = $this->makeRoundedRectLayerAA(
    $drawW, $drawH,
    $radiusPx,
    $boxFill, $boxBorder, $strokePx,
    /* withShadow */ false, /* offset */ 0, /* blur */ 0, /* color */ 'rgba(0,0,0,0)'
);
