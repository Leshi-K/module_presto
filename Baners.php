<?php

class Baners extends ObjectModel
{
	public $description;
	public $url;
	public $image;
	public $active;
	public $id;
	public $position;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'leshthreebaner',
		'primary' => 'id_leshthreebaner',
		'multilang' => true,
		'fields' => array(
			'active' =>			array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
			'position' =>		array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),

			// Lang fields
			'description' =>	array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 4000),
			'url' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'required' => true, 'size' => 255),
			'image' =>			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
		)
	);

	public	function __construct($id_baner = null, $id_lang = null, $id_shop = null, Context $context = null)
	{
		parent::__construct($id_baner, $id_lang, $id_shop);

	}

	public function getBaner($id_baner)
	{

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'leshthreebaner` WHERE id_leshthreebaner='.$id_baner);

	}

	public function add($autodate = true, $null_values = false)
	{

		$res = parent::add($autodate, $null_values);

		return $res;
	}

	public function delete()
	{
		$res = true;

		$images = $this->image;
		foreach ($images as $image)
		{
			if (preg_match('/sample/', $image) === 0)
				if ($image && file_exists(dirname(__FILE__).'/images/'.$image))
					$res &= @unlink(dirname(__FILE__).'/images/'.$image);
		}

		$res &= $this->reOrderPositions();

		$res &= Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'leshthreebaner`
			WHERE `id_leshthreebaner` = '.(int)$this->id
		);

		$res &= parent::delete();
		return $res;
	}

	public function reOrderPositions()
	{
		$id_slide = $this->id;

		$max = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(`position`) FROM `'._DB_PREFIX_.'leshthreebaner`'
		);

		if ((int)$max == (int)$id_slide)
			return true;

		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `position`, `id_leshthreebaner`
			FROM `'._DB_PREFIX_.'leshthreebaner`
			WHERE `position` > '.(int)$this->position
		);

		foreach ($rows as $row) {
			$current_slide = new Baners($row['id_leshthreebaner']);
			--$current_slide->position;
			$current_slide->update();
			unset($current_slide);
		}

		return true;
	}

}
