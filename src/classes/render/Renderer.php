<?php

namespace iutnc\touiteur\render;

interface Renderer {
    const COMPACT = 1;
    const LONG = 2;
    public function render(int $selector = 1): string;
}