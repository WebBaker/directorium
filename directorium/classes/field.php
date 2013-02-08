<?php
namespace Directorium;


class Field {
	const TYPE_TEXT_NORMAL = 'text';
	const TYPE_USER = 'user';

	public $type;
	public $name;
	public $id;
	public $label;
	public $default;


	public function __construct($name, $type = self::TYPE_TEXT_NORMAL, $label = '', $id = null, $default = '') {
		// Share name and ID?
		if ($id === null) $id = $name;

		$this->label = esc_attr(_x($label, 'field label', 'directorium'));
		$this->id = esc_attr($id);
		$this->name = esc_attr($name);
		$this->default = esc_attr($default);
		$this->type = $type;
	}


	public function __toString() {
		switch ($this->type) {
			case self::TYPE_TEXT_NORMAL: return $this->printTextField(); break;
			case self::TYPE_USER: return $this->printUserField(); break;
			default: return ''; break;
		}
	}


	protected function printTextField() {
		return View::load('field-text', array(
			'name' => $this->name,
			'id' => $this->id,
			'label' => $this->label,
			'default' => $this->default
		));
	}


	protected function printUserField() {
		return View::load('field-user', array(
			'name' => $this->name,
			'id' => $this->id,
			'label' => $this->label,
			'default' => $this->default
		));
	}
}