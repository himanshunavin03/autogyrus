<?php

if (! defined('ABSPATH')) {
    exit;
}

class ConflictResolutionService
{
    public function resolve($existing, $incoming)
    {
        return null !== $incoming ? $incoming : $existing;
    }
}
