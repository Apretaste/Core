<?php
/**
 * Model Person that map the Person table in the database
 *
 * @author hcarras
 */

use Phalcon\Mvc\Model;

class Person extends Models
{
    public $email;
    public $insertion_date;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $mother_name;
    public $date_of_birth;
    public $gender;
    public $phone;
    public $eyes;
    public $skin;
    public $body_type;
    public $hair;
    public $province;
    public $city;
    public $about_me;
    public $credit;
    public $active;
    public $last_update_date;
    public $updated_by_user;
    public $picture;
}
