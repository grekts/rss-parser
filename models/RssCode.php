<?php

namespace rssparser\models;

use yii\base\Model;
use yii\base\ErrorException;

/**
 * Модель кода rss лент
 * 
 * @author Roman Tsutskov
 */
class RssCode extends Model
{
	/**
	 * Метод парсинга RSS фида
	 * 
	 * @param string $url Ссылка на фид, подлежащий парсингу
	 * @return array Именованный массив с частями кода rss фида
	 * @access public
	 * @static
	 */
	public static function parseCode($url) {
		$dataType = gettype($url);
		if(($dataType === 'string') && ($url !== '')) {
			$domDoc = new \DOMDocument;
			//Загружаем фид
			try {
				$loadFlag = $domDoc->load($url);
			} catch(ErrorException $e) {
				throw new ErrorException("Не удалось загрузить rss файл");
			}
			//Если не произошла ошибка при получении контента фида
			if($loadFlag === true) {
				//Получаем списки новостей
				$items = $domDoc->getElementsByTagName('item');
				//Пробегаем каждую новость
				foreach($items as $codeOneItem) {
					//Получаем title
			        $newsTitleObject = $codeOneItem->getElementsByTagName('title');
			        $titleText = $newsTitleObject->item(0)->nodeValue;
			        $title = htmlspecialchars($titleText);
			        //Получаем description
			        $newsDescriptionObject = $codeOneItem->getElementsByTagName('description');
			        $description = htmlspecialchars($newsDescriptionObject->item(0)->nodeValue);
			        //Получаем ссылку на новость
			        $newsLinkObject = $codeOneItem->getElementsByTagName('link');
			        $link = htmlspecialchars($newsLinkObject->item(0)->nodeValue);
			        //Получаем дату публикации новости
			        $newsDateObject = $codeOneItem->getElementsByTagName('pubDate');
			        $publicationDate = htmlspecialchars($newsDateObject->item(0)->nodeValue);
			        $date = new \DateTime(trim($publicationDate));
					$publicationDate = $date->getTimestamp();
			        $rssData[] = ['title' => $title, 'description' => $description, 'link' => $link, 'publicationDate' => $publicationDate];
		    	}
		    	unset($url, $domDoc, $loadFlag, $items, $newsTitleObject, $titleText, $title, $newsDescriptionObject, $description);
		    	unset($newsLinkObject, $link, $newsDateObject, $codeOneItem, $publicationDate, $explodeDate, $day, $month, $year);
		    	unset($explodeTime, $hours, $minuts, $seconds, $publicationDate);

		    	return $rssData;
			} else {
				throw new ErrorException("Произошла ошибка при чтении RSS файла");
			}
		} else {
			if($dataType !== 'string') {
				throw new ErrorException("Тип данных входного параметра не соответствует типу string");
			}
			if($url === '') {
				throw new ErrorException("Не указаны данные во входном параметре");
			}
		}
	}

	/**
	 * Разделение текста по тегу <br>, чтобы получить массив с параграфами
	 * 
	 * @param string $data текст, подлежащий обработке
	 * @return array Нумерованный массив, где каждый элемент является одним текстовым параграфом
	 * @access public
	 * @static
	 */
	public static function devideTextByTag($data) {
		$dataType = gettype($data);
		if(($dataType === 'string') && ($data !== '')) {
			//Разделяем текст по тегам
			$explodeText = preg_split("/&lt;br.*&gt;/isU", $data, -1, PREG_SPLIT_NO_EMPTY);
			//Пробегаем каждую получившуюсячасти
		    foreach($explodeText as $onePartText) {
		    	//Удаляем пробелы
	    		$onePartText = trim($onePartText);
	    		if($onePartText !== '') {
	    			//Сохраняем текст в массив
	      			$devidedText[] = $onePartText;
	      		}
	 		}
	 		unset($data, $dataType, $explodeText, $onePartText);
	 		
	 		return $devidedText;
	 	} else {
	 		if($dataType !== 'string') {
	 			throw new ErrorException("Тип данных входного параметра не соответствует типу string");
	 		}
	 		if($data === '') {
	 			throw new ErrorException("Не указаны данные во входном параметре");
	 		}
	 	}
	}

	/**
	 * Удаление html тегов из указанной строки, кроме тегов <a> и <br>
	 * 
	 * @param string $data Текст, подлежащий обработке
	 * @return string Текст с удаленными тегами
	 * @access public
	 * @static
	 */
	public static function deleteHtmlTags($data) {
		$dataType = gettype($data);
		if(($dataType === 'string') && ($data !== '')) {
			//Удаляем все теги
			$clearData = strip_tags(html_entity_decode($data), '<a><br>');
			
			unset($dataType);

			return htmlspecialchars($clearData);
		} else {
			if($$dataType !== 'string') {
	 			throw new ErrorException("Тип данных входного параметра не соответствует типу string");
	 		}
			if($data === '') {
	 			throw new ErrorException("Не указаны данные во входном параметре");
	 		}
		}
	}

	/**
	 * К внешним ссылкам в тексте добавляет свойства rel=”nofollow” и target=”_blanck” и css класс(если он передан как параметр)
	 * 
	 * @param string $data Текст, ссылки в котором будут обрабатываться
	 * @param string $cssClass Имя CSS свойства, которое будет применено к ссылке. Может быть пустым
	 * @return string Текст с замененными ссылками
	 * @access public
	 * @static
	 */
	public static function convertCodeExternalLinks($data, $cssClass)
    {
    	$dataType = gettype($data);
    	$dataType2 = gettype($cssClass);
    	if(($dataType === 'string') && ($dataType2 === 'string') && ($data !== '')) {
		    $urlTags = '';
		    //Получаем ссылки
	        $numberFindedUrlTags = preg_match_all("/&lt;a.*\/a&gt;/isU", $data, $urlTags);
	        if(($numberFindedUrlTags !== 0) && ($numberFindedUrlTags !== false)) {
	        	//Пробегаем все найденные ссылки
	        	for($j = 0; $j < $numberFindedUrlTags; $j++) {
	            	$codeWithUrl = '';
		            $urlDescription = '';
		            //Получаем саму ссылку из кода со ссылкой
		            $findCodeUrlInUrlTags = preg_match("/href.*(&quot;|&#039;).*(&quot;|&#039;)/isU", $urlTags[0][$j], $codeWithUrl);
		            //Получаем описание из ссылки
		            $findDescriptionUrlInUrlTags = preg_match("/&gt;.*&lt;/isU", $urlTags[0][$j], $urlDescription);
		            //Если нашли ссылки в кодах ссылок и не было ошибок при поиске
	            	if(($findCodeUrlInUrlTags !== 0) 
		                && ($findDescriptionUrlInUrlTags !== 0)
		                && ($findCodeUrlInUrlTags !== false) 
		                && ($findDescriptionUrlInUrlTags !== false)) {
	            		//Очищаем код со ссылкой
			            $clearUrl = preg_replace("/href|=|&quot;|&#039;/isU", '', $codeWithUrl[0]);
			        	//очищаем описание
			            $clearDescription = preg_replace("/\/|&lt;|&gt;/isU", '', $urlDescription[0]);
			            //Если было указано имя CSS класса, который нужно привязать ссылке
		            	if($cssClass !== '') {
		            		$cssClassCode = 'class=&quot;'.$cssClass.'&quot;';
		            	} else {
		            		$cssClassCode = '';
		            	}
		            	//Форимруем новый код ссылки
	          			$newUrl = '&lt;a href=&quot;'.$clearUrl.'&quot; '.$cssClassCode.' target=&quot;_blanck&quot; rel=&quot;nofollow&quot;&gt;'.$clearDescription.'&lt;/a&gt;';
	          			//Экранируем код со ссылкой, чтобы его можно использовать в регулярке далее
		                $quoteUrlCode = preg_quote($urlTags[0][$j], '/');
		                //Переделываем описание ссылки в нижний регистр
		                $lowerClearDescription = mb_strtolower($clearDescription, 'UTF-8');
		                //Есть нет указанного слова, знаичт скорее всего это сслыка не типа "читать далее"
		                if(strpos($lowerClearDescription, 'читать') === false) {
		                	//Заменяем старый код ссылки на новый
		                	$data = preg_replace("/$quoteUrlCode/isU", $newUrl, $data);
		            	} else {
		            		//Заменяем ссылку на пустое
		            		$data = preg_replace("/$quoteUrlCode/isU", '', $data);
		            	}
	            	}
	        	}
	        	unset($cssClass, $urlTags, $numberFindedUrlTags, $j, $codeWithUrl, $urlDescription, $findCodeUrlInUrlTags);
	        	unset($findDescriptionUrlInUrlTags, $clearUrl, $clearDescription, $cssClassCode, $newUrl, $quoteUrlCode);

	        	return $data;
	        } else { //Если ссылок не нашлось или произошла ошибка
	        	if($numberFindedUrlTags === false) {
	        		throw new ErrorException("Произошла ошибка при поиске в обрабатывемом тексте кодов ссылок");
	        	} 
	        	//Усли не нашли внешние ссылки
	        	if($numberFindedUrlTags === 0) {
	        		unset($cssClass, $urlTags, $numberFindedUrlTags);

	        		return $data;
	        	}
	        }
	    } else {
	    	if($dataType !== 'string') {
	    		throw new ErrorException("Тип данных входного параметра не соответствует типу string");
	    	}
	    	if($dataType2 !== 'string') {
	    		throw new ErrorException("Тип данных входного параметра не соответствует типу string");
	    	}
	    	if($data === '') {
	    		throw new ErrorException("Не указаны данные во входном параметре");
	    	}
	    }
    }
}