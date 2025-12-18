<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Illuminate\Support\Facades\Request;

class CorrelationIdProcessor implements ProcessorInterface
{
    /**
     * Add correlation ID to log record
     *
     * @param LogRecord $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // Get correlation ID from request header or context
        $correlationId = Request::header('X-Correlation-ID') 
            ?? $record->context['correlation_id'] 
            ?? 'no-correlation-id';
        
        // Add correlation ID to extra data
        $record->extra['correlation_id'] = $correlationId;
        
        return $record;
    }
}

