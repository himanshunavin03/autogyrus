<?php

if (! defined('ABSPATH')) {
    exit;
}

class ValidationPipeline
{
    public function validate(array $record)
    {
        return AutoGyrus_Import_Service::validate_record($record);
    }
}
