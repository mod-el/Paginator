<?php namespace Model\Paginator;

class Paginator
{
	/** @var array */
	public $options = [
		'tot' => null, // Total number of elements
		'perPage' => null, // Elements per page
		'totPag' => null, // Total number of pages (you can set either this or the previous two options)
		'pag' => 1, // Current page
		'limit' => 9, // Pages to be shown to the right and to the left of the current one
		'limit_left' => null, // Eventual specific number of pages for the left side
		'limit_right' => null, // Eventual specific number of pages for the right side
		'forward' => '&gt;', // "Next" button
		'backward' => '&lt;', // "Previous" button
		'start' => 'Inizio', // "Start" button
		'end' => 'Fine', // "End" button

		'alphabetic' => false, // Should I show the letters instead of the numbers?
		'englishLetters' => true, // English alphabet? Or (as default) Italian one?
		'uppercase' => true, // The letters should be all uppercase?
	];
	/** @var int Total number of pages */
	public $tot = null;
	/** @var int Current page */
	public $pag = null;

	public function __construct(array $options = [])
	{
		$this->options['forward'] = '<img src="' . PATH . 'model/Paginator/assets/img/right-arrow.png" alt="" />';
		$this->options['backward'] = '<img src="' . PATH . 'model/Paginator/assets/img/left-arrow.png" alt="" />';
		$this->setOptions($options);
	}

	public function setOptions(array $opt): bool
	{
		$this->options = array_merge($this->options, $opt);
		return $this->calculate();
	}

	private function calculate(): bool
	{
		if ($this->options['alphabetic']) {
			$this->tot = $this->options['englishLetters'] ? 26 : 21;
		} elseif ($this->options['totPag'] !== null) {
			$this->tot = $this->options['totPag'];
		} elseif ($this->options['tot'] !== null) {
			if ($this->options['perPage'])
				$this->tot = (int)ceil($this->options['tot'] / $this->options['perPage']);
			else
				$this->tot = 1;
		} else {
			return false;
		}

		if ($this->tot < 1)
			$this->tot = 1;

		if ($this->options['alphabetic']) {
			if (
				!preg_match('/^[a-z]$/i', $this->options['pag'])
				or (!$this->options['englishLetters'] and !in_array(strtolower($this->options['pag']), ['x', 'y', 'w', 'j', 'k']))
			) {
				$this->options['pag'] = $this->options['uppercase'] ? 'A' : 'a';
			}
		} else {
			if (!is_numeric($this->options['pag']))
				$this->options['pag'] = 1;
			if ($this->options['pag'] > $this->tot)
				$this->options['pag'] = $this->tot;
			if ($this->options['pag'] < 1)
				$this->options['pag'] = 1;
		}
		$this->pag = $this->options['pag'];

		return true;
	}

	public function getLimit(): string
	{
		return $this->getStartLimit() . ',' . $this->options['perPage'];
	}

	public function getStartLimit(): int
	{
		return ($this->pag - 1) * $this->options['perPage'];
	}

	public function get(array $opt = []): array
	{
		if (!empty($opt))
			$this->setOptions($opt);

		if ($this->options['limit_left'] === null)
			$this->options['limit_left'] = $this->options['limit'];
		if ($this->options['limit_right'] === null)
			$this->options['limit_right'] = $this->options['limit'];

		if ($this->options['alphabetic']) {
			$paragoneInizio = $this->options['uppercase'] ? 'A' : 'a';
			$paragoneFine = $this->options['uppercase'] ? 'Z' : 'z';

			if ($this->options['uppercase']) {
				$paginazione_start = ord('A');
				$paginazione_end = ord('Z');
			} else {
				$paginazione_start = ord('a');
				$paginazione_end = ord('z');
			}
		} else {
			$paragoneInizio = 1;
			$paragoneFine = $this->tot;

			if ($this->tot <= $this->options['limit_left'] + $this->options['limit_right']) {
				$paginazione_start = 1;
				$paginazione_end = $this->tot;
			} else {
				$paginazione_start = $this->options['pag'] - $this->options['limit_left'];
				if ($paginazione_start < 1) $paginazione_start = 1;
				$paginazione_end = $this->options['pag'] + $this->options['limit_right'];
				if ($paginazione_end > $this->tot) $paginazione_end = $this->tot;
			}
		}

		$pagine = [];
		if ($this->options['pag'] > $paragoneInizio) {
			$indietro = $this->options['alphabetic'] ? chr(ord($this->options['pag']) - 1) : $this->options['pag'] - 1;
			if ($this->options['start']) $pagine[] = ['text' => $this->options['start'], 'p' => $paragoneInizio, 'current' => false, 'special' => true];
			if ($this->options['backward']) $pagine[] = ['text' => $this->options['backward'], 'p' => $indietro, 'current' => false, 'special' => true];
		}
		for ($cp = $paginazione_start; $cp <= $paginazione_end; $cp++) {
			if ($this->options['alphabetic']) {
				$cpt = chr($cp);
				if (!$this->options['englishLetters'] and !in_array(strtolower($cpt), ['x', 'y', 'w', 'j', 'k']))
					continue;
			} else {
				$cpt = $cp;
			}
			$current = $cpt == $this->options['pag'];
			$pagine[] = ['text' => $cpt, 'p' => $cpt, 'current' => $current, 'special' => false];
		}
		if ($this->options['pag'] < $paragoneFine) {
			$avanti = $this->options['alphabetic'] ? chr(ord($this->options['pag']) + 1) : $this->options['pag'] + 1;
			if ($this->options['forward']) $pagine[] = ['text' => $this->options['forward'], 'p' => $avanti, 'current' => false, 'special' => true];
			if ($this->options['end']) $pagine[] = ['text' => $this->options['end'], 'p' => $paragoneFine, 'current' => false, 'special' => true];
		}

		return $pagine;
	}

	public function render(array $opt = [], bool $return = false)
	{
		$pages = $this->get();
		if (count($pages) <= 1)
			return '';

		$qry = $_GET;
		unset($qry['url']);
		$qry['p'] = '[p]';
		$qry = str_replace('%5Bp%5D', '[p]', http_build_query($qry));

		$options = array_merge([
			'on' => '<span class="zkpag-on">[text]</span>',
			'off' => '<a href="?' . $qry . '" class="zkpag-off">[text]</a>',
			'separator' => ' ',
		], $opt);

		if (!array_key_exists('special', $options))
			$options['special'] = str_replace('zkpag-off', 'zkpag-special', $options['off']);

		$echo = '';
		foreach ($pages as $cp => $p) {
			$base = null;
			if ($cp > 0) $echo .= $options['separator'];
			if ($p['special']) $base = $options['special'];
			elseif ($p['current']) $base = $options['on'];
			else $base = $options['off'];

			$text = $p['text'];
			if (!$p['special'])
				$text = entities($p['text']);

			if ($base)
				$echo .= str_replace('[text]', $text, str_replace('[p]', $p['p'], $base));
		}

		if ($return) return $echo;
		else echo $echo;
	}
}
