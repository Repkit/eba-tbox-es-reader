<?php

namespace TBoxESReader\Interfaces;

interface ESFilterInterface
{
	/**
     * Retrieve filtered collections
     *
     * @return array|bool
     *   Filtered collection array or false in case filter data is not valid
     */
    public static function createQuery(array $Filter, array $Mapping = array());

}
