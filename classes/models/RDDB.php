<?php

namespace PSO\Models;

use PSO\Helpers\Helper;
use \Redis;

class RDDB extends \wpdb
{
    private ?Redis $redis;

    private string $redis_host;

    private int $redis_port;

    private int $cache_time;

    /**
     * Constructor to initialize the database connection and Redis caching.
     */
    public function __construct()
    {
        global $wpdb;

        if ($wpdb instanceof \wpdb && isset($wpdb->dbh) && $wpdb->dbh instanceof \mysqli) {
            foreach (get_object_vars($wpdb) as $key => $value) {
                if ($key !== 'dbh' && $key !== 'result') {
                    $this->$key = $value;
                }
            }
            $this->dbh = $wpdb->dbh;
            $this->ready = true;
            $this->use_mysqli = true;
        } else {
            parent::__construct(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        }

        $this->redis = null;
        $this->redis_host = Helper::getSetting('redis_host') ?? 'localhost';
        $this->redis_port = Helper::getSetting('redis_port') ?? 6379;
        $this->cache_time = 3600;

        if (Helper::redisEnabled(true) && class_exists('Redis')) {
            try {
                $this->redis = new \Redis();
                $this->redis->pconnect($this->redis_host, $this->redis_port);
                if ($this->redis->ping() === '+PONG') {
                    if (Helper::redisEnabled()) {
                        $GLOBALS['wpdb'] = $this;
                    }
                    delete_transient('pso_redis_connection_error');
                } else {
                    $error_message = sprintf(
                        'Redis connection failed: Could not PING server at %s:%s.',
                        esc_html($this->redis_host),
                        esc_html($this->redis_port)
                    );
                    error_log($error_message);
                    Helper::disableRedis();
                    set_transient('pso_redis_connection_error', $error_message, 60);
                }
            } catch (\RedisException $e) {
                // Connection failed (exception caught)
                $error_message = sprintf(
                    'Redis connection failed with an error at %s:%s: %s',
                    esc_html($this->redis_host),
                    esc_html($this->redis_port),
                    esc_html($e->getMessage())
                );
                error_log($error_message);
                Helper::disableRedis();
                set_transient('pso_redis_connection_error', $error_message, 60);
            }
        } else {
            $this->redis = null;
            delete_transient('pso_redis_connection_error');
        }
    }

    /**
     * Generate a cache key based on the SQL query.
     *
     * @param string $query
     * @return string
     */
    private function get_cache_key($query)
    {
        return 'rddb_' . md5($query);
    }

    /**
     * Retrieve cached data for a given query.
     *
     * @param string $query
     * @return mixed|false
     */
    private function cache_get($query)
    {
        if ($this->should_cache($query)) {
            $cached_data = $this->redis->get($this->get_cache_key($query));
            return $cached_data ? unserialize($cached_data) : false;
        }
        return false;
    }


    /**
     * Checks if the query must be cached
     *
     * @param string $query
     */
    private function should_cache($query)
    {
        if (!$query || !$this->redis || Helper::maybeBypassRedis() || !Helper::redisEnabled()) {
            return false;
        }

        // Exclude WooCommerce cart, session, and other dynamic queries
        $excluded_tables = [
            'wc_cart', 'woocommerce_', 'wc_sessions', 'wc_orders', 'wc_order_',
            'user', 'role', 'capabilities' // Exclude user-related queries
        ];

        foreach ($excluded_tables as $table) {
            if (str_contains(strtolower($query), $table)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Delete cached data for a given query.
     *
     * @param string $query
     */
    private function cache_delete($query)
    {
        if ($this->redis) {
            $this->redis->del($this->get_cache_key($query));
        }
    }

    /**
     * Cache the result of a query.
     *
     * @param string $query
     * @param mixed $data
     */
    private function cache_set($query, $data)
    {
        if ($this->should_cache($query)) {
            if(str_contains($query, 'rank_math') && preg_match('/rank\\\\?_math\\\\?_schema/i', $query)) {
                // skip caching Rank Math queries
                return;
            }
            $serialized_data = serialize($data);
            $data_size = strlen($serialized_data);

            if ($data_size > 1024 * 1024) { // 1MB max size
                return;
            }

            $this->redis->setex($this->get_cache_key($query), $this->cache_time, $serialized_data);
        }
    }

    /**
     * Retrieve multiple results from a query with caching.
     *
     * @param string|null $query
     * @param string $output
     * @return array|object|null
     */
    public function get_results($query = null, $output = OBJECT)
    {
        if (!$query) {
            return parent::get_results($query, $output);
        }

        $cached_data = $this->cache_get($query);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $results = parent::get_results($query, $output);
        if ($results !== false) {
            $this->cache_set($query, $results);
        }

        return $results;
    }

    /**
     * Retrieve a single row from a query with caching.
     *
     * @param string|null $query
     * @param string $output
     * @param int $y
     * @return object|null
     */
    public function get_row($query = null, $output = OBJECT, $y = 0)
    {
        $cached_data = $this->cache_get($query);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $result = parent::get_row($query, $output, $y);
        if ($result !== false) {
            $this->cache_set($query, $result);
        }

        return $result;
    }

    /**
     * Retrieve a single variable from a query with caching.
     *
     * @param string|null $query
     * @param int $x
     * @param int $y
     * @return mixed|null
     */
    public function get_var($query = null, $x = 0, $y = 0)
    {
        $cached_data = $this->cache_get($query);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $result = parent::get_var($query, $x, $y);
        if ($result !== false) {
            $this->cache_set($query, $result);
        }

        return $result;
    }

    /**
     * Retrieve a single column from a query with caching.
     *
     * @param string|null $query
     * @param int $x
     * @return array|null
     */
    public function get_col($query = null, $x = 0)
    {
        $cached_data = $this->cache_get($query);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $result = parent::get_col($query, $x);
        if ($result !== false) {
            $this->cache_set($query, $result);
        }

        return $result;
    }

    /**
     * Execute a query and clear cache for modifying queries.
     *
     * @param string $query
     */
    public function query($query)
    {
        $is_fetch = preg_match('/^\s*(SELECT|SHOW)/i', $query);
        if($is_fetch) {
            $cached_data = $this->cache_get($query);
            if ($cached_data !== false) {
                return $cached_data;
            }
        }
//		elseif($query &&
//			!str_contains($query, '_transient') &&
//			!str_contains($query, 'icl_mo_files_domains') &&
//			!str_contains($query, 'options') &&
//			!str_contains($query, 'actionscheduler')) {
//			$this->flushAll($query);
//		}

        $result = parent::query($query);

        if ($is_fetch && $result !== false) {
            $this->cache_set($query, $result);
        }
        return $result;
    }

    /**
     * Flush all cache stored in Redis.
     */
    public function flushAll()
    {
        if($this->redis) {
            $this->redis->flushAll();
        }
    }
}