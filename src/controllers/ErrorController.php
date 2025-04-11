<?php

use JSONms\Controllers\RestfulController;

class ErrorController extends RestfulController
{
    public function saveAction($errors)
    {
        foreach ($errors as $error) {
            $this->query('insert-error', [
                'key' => $error->key,
                'message' => $error->message,
                'source' => $error->source,
                'line' => $error->line,
                'column' => $error->column,
                'stack' => $error->stack,
                'occurred_on' => $error->occurred_on,
                'last_timestamp' => $error->last_timestamp,
                'version' => $error->version,
                'route' => $error->route,
                'count' => $error->count,
                'user_agent' => $error->user_agent,
                'created_by' => $this->getCurrentUserId(),
            ]);
        }

        $this->responseJson(true);
    }
}
