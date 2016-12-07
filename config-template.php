<?php

    class Config
    {
        const REDMINE_URL         = 'LINK_TO_REDMINE';
        const API_KEY             = 'API_KEY';
        const STATUS_RESOLVED     = 3;
        const STATUS_CLOSED       = 5;
        const CLOSE_IF_OLDER_THEN = '2 weeks';

        public static function get($key)
        {
            if (!defined('self::'.$key)) {
                return;
            }

            return constant('self::'.$key);
        }
    }
