<?php

namespace iutnc\touiteur\render;

use iutnc\touiteur\touite\Touite;

class RendererFactory {
    public static function getRenderer(mixed $obj): Renderer {
        return match (true) {
            $obj instanceof Touite => new TouiteRenderer($obj),
            default => throw new \InvalidArgumentException('Aucun renderer pour ' . get_debug_type($obj)),
        };
    }
}