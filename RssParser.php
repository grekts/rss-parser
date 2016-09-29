<?php

namespace rssparser;

use rssparser\models\RssCode;

/**
* Класс для парсинга файлов rss лент
*
* @author Roman Tsutskov
*/

class RssParser
{
	/**
	*
	* Метод парсинга rss лент
	*
	* @param string $url Ссылка на фид, который нужно парсить
	* @param string $cssClass Имя css класса, который будет прикреплен ко внешним ссылкам (если указан)
	* @return array Массив с новосятми. Структура массива [[0] => ['title' => value, 'description' => value, 'link' => value, 'publicationDate' => value], [1] => ...]. Значение 'description' является массивом в формате json, где каждый элемент является абзацем текста
	* @access public
	*
	*/
	public function parse($url, $cssClass = '') {
		//Получаем код фида
		$rssCode = RssCode::parseCode($url);
		foreach ($rssCode as $key => $oneRssCode) {
			//Удаляем html теги, кроме <br> и <a>
			$textDeleteHtmlTags = RssCode::deleteHtmlTags($oneRssCode['description']);
			//Изменяем коды ссылок, добавляя в них rel="nofollow", target="_blank" и css класс, если он был указан
			$textUpdateExternalLink = RssCode::convertCodeExternalLinks($textDeleteHtmlTags, $cssClass);
			//Разделяем описание на параграфы
			$devideText = RssCode::devideTextByTag($textUpdateExternalLink);
			//Заменяем в полученных данных старое описание на новое
			$rssCode[$key]['description'] = $devideText;
		}
		
		return $rssCode;
	}
}