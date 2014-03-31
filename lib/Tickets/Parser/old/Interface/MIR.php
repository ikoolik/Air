<?php

/**
 * Класс-парсер для .MIR файлов от GDS Galileo
 * 
 * TODO билет на несколько пассажиров!
 * TODO сборка билета из нескольких файлов
 * 
 * @author ikoolik
 *
 */
class GDS_Parser_MIR {
	
	private $aDefaults = array();
	
	public function __construct($aDefaults) {
		$this->aDefaults = $aDefaults;
	}
	
	/**
	 * Метод возвращает массив данных о билете, сформированный из файла
	 *
	 * @param string $sFile путь у файлу
	 * @return array массив данных о билете, полученный из файла
	 */
	public function getTicketFromFile($sFile) {
		//забираем содержимое файла
		$sMirFile = file_get_contents($sFile);
		//дробим содержимое на секции
		$aMirSections = explode("\r\r",$sMirFile);
		//последняя секция - указатель на конец файла нахер нам не нужна - убираем ее
		$aMirSections = array_slice($aMirSections, 0, (count($aMirSections) - 1));
		
		$aMir = array();
		
		$sSectionCode = 'Header'; //название начальной секции
		foreach($aMirSections as $sSectionText) { //обрабатываем каждую секцию
			//проверяем, есть ли у секции идентификатор
			if(preg_match("#^A\d\d#", $sSectionText, $aMatch)) {
				$sSectionCode = $aMatch[0];
			}
			//парсим секцию, результат - в сводный массив
			$aMir[$sSectionCode] = $this->parseSection($sSectionText, $sSectionCode);
		}
		//var_dump($aMir);
		$iFlightNum = 0;
		$aVariant = array();
		//собираем массив сегментов
		foreach($aMir['A04'] as $aMirFlightPart) {
			$oDepApt = Biletoid_References::getAirportByCode($aMirFlightPart['DepCityCode']);
			$oArrApt = Biletoid_References::getAirportByCode($aMirFlightPart['ArrCityCode']);
			$oAirline = Biletoid_References::getAirlineByCode($aMirFlightPart['AirlineCode']);
			$sDepDate = date("Y-m-d",strtotime($aMirFlightPart['DepDate']));
			$sArrDate = ($aMirFlightPart['NextDayArrival'] == 2) ? $sDepDate : date("Y-m-d",(strtotime($sDepDate) +60*60*24)); 
			$aFlightPart = array(
					'groupId' => ($iFlightNum+1),
					'platingCarrierId' => $oAirline->id,
					'flight' => array(
							'code' => $aMirFlightPart['FlightNum'],
							'fromCityId' => $oDepApt->city_id,
							'toCityId' => $oArrApt->city_id,
							'fromAirportId' => $oDepApt->id,
							'toAirportId' => $oArrApt->id,
							'airlineCode' => $oAirline->code,
							'dateBegin' => $sDepDate,
							'timeBegin' => date("H:i:s",strtotime($aMirFlightPart['DepTime'])),
							'dateEnd' => $sArrDate,
							'timeEnd' => $aMirFlightPart['ArrTime'],
							'distance' => (int) $aMirFlightPart['NauticalMiles'],
							'aircraft' => array(
									'name' => $aMirFlightPart['Aircraft'],
									'code' => $aMirFlightPart['Aircraft'],
							),
							'fromTerminal' => array(
									'code' => $aMirFlightPart['DepTerminal']
							),
					)
			);
			$aVariant['variants'][] = $aFlightPart;
			
			//обрабатываем указатель на группы сегментов
			//значение Х в StopoverIndicators указывает на то, 
			//что пункт назначения егмента не конечный пункт перелета, но пересадка
			if($aMirFlightPart['StopoverIndicators'] != 'X') {
				$iFlightNum++;
			};
		}
		$aVariant['priceDetail'] = array(
				array(
						'totalValue' => $this->getTicketPrice($aMir)
				)
		);
		$oVartiant = new Biletoid_Variant($aVariant, array('why do we need this array? T_T'));
		$aPassports = array(array(
				'first_name' => ucwords(strtolower($aMir['A02']['first_name'])),
				'last_name' => ucwords(strtolower($aMir['A02']['last_name'])),
				'sex_id' => $aMir['A02']['sex'],
				'ticket_number' => $aMir['A04'][0]['AirlineNum'].$aMir['A02']['TKT'],
				//дальше - дефолтные значения
				'country_id' => $this->aDefaults['passport']['country_id'],
				'type_id' => $this->aDefaults['passport']['type_id'],
				'number' => $this->aDefaults['passport']['number'],
				'birthday' => $this->aDefaults['passport']['birthday'],
				'user_id' => $this->aDefaults['user_id']
		));
		return array(
				'oVariant' => $oVartiant,
				'pnr' => $aMir['Header']['pnr'],
				'TKT' => $aMir['A04'][0]['AirlineNum'].$aMir['A02']['TKT'],
				'aPassports' => $aPassports
				);
	}
	

	
	private function getTicketPrice($aMir) {
		return (int) $aMir['A07']['FullFare'];
	}
	

	
	/**
	 * Метод парсер для секции A00 (CUSTOMER REMARK (APO))
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA00Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A01 (CORPORATE NAME)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA01Section($sSectionText) {
		return null;
	}
	

	
	/**
	 * Метод парсер для секции A03 (FREQUENT FLYER DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA03Section($sSectionText) {
		return null;
	}
	

	
	/**
	 * Метод парсер для секции A05 (WAITLIST/OTHER AIR DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA05Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A06 (APOLLO SEAT DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA06Section($sSectionText) {
		return null;
	}
	

	
	/**
	 * Метод парсер для секции A08 (FARE BASIS SECTION)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */

	
	/**
	 * Метод парсер для секции A09 (FARE CONSTRUCTION SECTION)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */

	
	/**
	 * Метод парсер для секции A10 (EXCHANGE TICKET INFORMATION)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA10Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A11 (FORM OF PAYMENT DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */

	
	/**
	 * Метод парсер для секции A12 (PHONE DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */

	
	/**
	 * Метод парсер для секции A13 (ADDRESS DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA13Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A14 (BOS/TICKET REMARKS)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */

	
	/**
	 * Метод парсер для секции A15 (ASSOCIATED/UNASSOCIATED REMARKS)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA15Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A16 (AUXILIARY DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA16Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A17 (LEISURESHOPPER DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA17Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A18 (ETDN INFORMATION)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA18Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A19 (MISCELLANEOUS DOCUMENTS)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA19Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A20 (SSR/OSI DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA20Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A21 (NET REMIT)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA21Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A22 (GALILEO SEAT DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA22Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A23 (REFUND DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA23Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A24 (OTHER FARE CONSTRUCTION)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA24Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод парсер для секции A26 (NON HOST CONTENT DATA)
	 * @param string $sSectionText секция в текстовом формате
	 * @return array массив данных секции
	 */
	private function parseA26Section($sSectionText) {
		return null;
	}
	
	/**
	 * Метод обрабатывает строку необязательных параметров
	 * 
	 * @param string $sString строка необязательных параметров
	 * @param integer $iKeyLenght количество символов в индексе параметра
	 * @return array ассоциативный массив параметров
	 */

}

?>