<?php
/**
 * Model Ads that map the Ads table in the database
 *
 * @author hcarras
 */

use Phalcon\Mvc\Model;

class Ads extends Models
{
    public $ads_id;
    public $time_inserted;
    public $active;
    public $impresions;
    public $owner;
    public $title;
    public $description;
    public $expiration_date;
    public $paid_date;
}
