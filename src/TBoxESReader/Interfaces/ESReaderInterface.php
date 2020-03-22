<?php

namespace TBoxESReader\Interfaces;

interface ESReaderInterface
{
	public function getAllByIndex($Index, $Filter = array());
	public function getByIndexAndId($Index,$Id);
}