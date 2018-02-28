<?php

namespace AirlineServiceDemo\Services;

interface IService
{
	public function execute(array $data):bool;
}
