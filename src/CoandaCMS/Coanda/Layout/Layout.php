<?php namespace CoandaCMS\Coanda\Layout;

use Coanda, App, View;

/**
 * Class Layout
 * @package CoandaCMS\Coanda\Layout
 */
abstract class Layout {

    /**
     * @var
     */
    private $blocks;
    /**
     * @var
     */
    private $block_repository;

    /**
     * @return mixed
     */
    abstract public function identifier();

    /**
     * @return mixed
     */
    abstract public function template();

    /**
     * @return mixed
     */
    abstract public function name();

    /**
     * @return array
     */
    public function pageTypes()
	{
		return [];
	}
}
