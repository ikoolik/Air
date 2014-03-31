<?php

define("CITY" , 'города');
define("AIRPORT" , 'аэропорта');

class GDS_Parser_SIRENA {
	
	private $sErrorsEmail = 'ivan.kulikov@planetoid.ru';
	
	private $aDefaults = array();
	
	public function __construct($aDefaults) {
		$this->aDefaults = $aDefaults; //забираем значения по-умолчанию
	}
	
	/**
	 * Метод возвращает массив данных о билете, сформированный из файла
	 *
	 * @param string $sFile путь у файлу
	 * @return array массив данных о билете, полученный из файла
	 */
	public function getTicketFromFile($sFile) {
		//забираем содержимое файла
		$sXML = file_get_contents($sFile);
		$oXML = new SimpleXMLElement($sXML);
		
		$aTicket = array();
		
		$sTKT = (string) $oXML->TICKET->BSONUM;
		$sCarrierId = (string) $oXML->TICKET->GENERAL_CARRIER;
		
		$aTicket['TKT'] = $sCarrierId.substr($sTKT, 2);
		$aTicket['pnr'] = (string)$oXML->TICKET->PNR_LAT;
		
		$sNameString = $oXML->TICKET->FIO;
		list($sLastName, $sFirstNameAndSex) = explode("/",$sNameString);
		list($sFirstName, $sSex) = explode(" ", $sFirstNameAndSex);
		$iSexId = ($sSex == 'Г') ? 1 : 2;
		
		$aPassports = array(
				array(
						'first_name' => mb_convert_case($sFirstName,MB_CASE_TITLE,'utf-8'),
						'last_name' => mb_convert_case($sLastName,MB_CASE_TITLE,'utf-8'),
						'sex_id' => $iSexId,
						//дальше - дефолтные значения
						'country_id' => $this->aDefaults['passport']['country_id'],
						'type_id' => $this->aDefaults['passport']['type_id'],
						'number' => $this->aDefaults['passport']['number'],
						'birthday' => $this->aDefaults['passport']['birthday'],
						'user_id' => $this->aDefaults['user_id']
						
				)
		);
		$aTicket['aPassports'] = $aPassports;
		
		$iFlightNum = 0;
		$iPrice = 0;
		foreach($oXML->TICKET->SEGMENTS->SEGMENT as $oSegment) {
			//проверяем аэропорт вылета
			$sDepAptCode = (string) $oSegment->PORT1CODE;
			$sDepAptCodeLen = strlen($sDepAptCode);
			if($sDepAptCodeLen == 3) {
				//IATA код
				$oDepApt = Biletoid_References::getAirportByCode($sDepAptCode);
			}else if($sDepAptCodeLen == 6){
				//СССР код
				$oDepApt = Biletoid_References::getAirportByRusCode($sDepAptCode);
			}
			if(!$oDepApt && $sDepAptCodeLen) {
				$this->sendEmailAboutUnknownCode(AIRPORT, $sDepAptCode);
				//НЕИЗВЕСТНЫЙ КОД АЭРОПОРТА $sDepAptCode
			}
			
			//Проверяем аэропорт прилета
			$sArrAptCode = (string) $oSegment->PORT2CODE;
			$sArrAptCodeLen = strlen($sArrAptCode);
			if($sArrAptCodeLen == 3) {
				//IATA код
				$oArrApt = Biletoid_References::getAirportByCode($sArrAptCode);
			}else if($sArrAptCodeLen == 6){
				//СССР код
				$oArrApt = Biletoid_References::getAirportByRusCode($sArrAptCode);
			}
			if(!$oArrApt && $sArrAptCodeLen) {
				//НЕИЗВЕСТНЫЙ КОД АЭРОПОРТА $sArrAptCode
				$this->sendEmailAboutUnknownCode(AIRPORT, $sArrAptCode);
			}
			
			//Проверяем город вылета
			$sDepCityCode = (string) $oSegment->CITY1CODE;
			$sDepCityCodeLen = strlen($sDepAptCode);
			if($sDepCityCodeLen == 3) {
				//IATA код
				$oDepCity = Biletoid_References::getCityByCode($sDepCityCode);
			}else if($sDepCityCodeLen == 6){
				//СССР код
				$oDepCity = Biletoid_References::getCityByRusCode($sDepCityCode);
			}
			if(!$oDepCity && $sDepCityCodeLen) {
				//НЕИЗВЕСТНЫЙ КОД ГОРОДА $sDepCityCode
				$this->sendEmailAboutUnknownCode(CITY, $sDepCityCode);
			}
			
			//Проверяем город прилета
			$sArrCityCode = (string) $oSegment->CITY2CODE;
			$sArrCityCodeLen = strlen($sArrAptCode);
			if($sArrAptCodeLen == 3) {
				//IATA код
				$oArrCity = Biletoid_References::getCityByCode($sArrCityCode);
			}else if($sArrAptCodeLen == 6){
				//СССР код
				$oArrCity = Biletoid_References::getCityByRusCode($sArrCityCode);
			}
			if($oArrCity && $sArrCityCodeLen) {
				//НЕИЗВЕСТНЫЙ КОД ГОРОДА $sArrCityCode
				$this->sendEmailAboutUnknownCode(CITY, $sArrCityCode);
			}
			
			$sAirlineCode = (string) $oSegment->CARRIER;
			$oAirline = Biletoid_References::getAirlineByCode($sAirlineCode);
			
			$sDepDate = substr($oSegment->FLYDATE, 4)."-".substr($oSegment->FLYDATE, 2, 2)."-".substr($oSegment->FLYDATE, 0, 2);
			$sDepTime = date("H:i:s",strtotime((string) $oSegment->FLYTIME));
			
			$sArrDate = substr($oSegment->ARRDATE, 4)."-".substr($oSegment->ARRDATE, 2, 2)."-".substr($oSegment->ARRDATE, 0, 2);
			$sArrTime = date("H:i:s",strtotime((string) $oSegment->ARRTIME));
			
			$aFlightPart = array(
					'groupId' => ($iFlightNum+1),
					'platingCarrierId' => $oAirline->id,
					'flight' => array(
							'code' => (string) $oSegment->REIS,
							'fromCityId' =>($oDepCity->id) ? $oDepCity->id : $this->aDefaults['city_id'],
							'toCityId' => ($oArrCity->id) ? $oArrCity->id : $this->aDefaults['city_id'],
							'fromAirportId' => ($oDepApt->id) ? $oDepApt->id : $this->aDefaults['airport_id'],
							'toAirportId' => ($oArrApt->id) ? $oArrApt->id : $this->aDefaults['airport_id'],
							'airlineCode' => $oAirline->code,
							'dateBegin' => $sDepDate,
							'timeBegin' => $sDepTime,
							'dateEnd' => $sArrDate,
							'timeEnd' => $sArrTime,
							'distance' => 1000,
							'aircraft' => array(
									'name' => '',
									'code' => '',
							),
					)
			);
			
			$aVariant['variants'][] = $aFlightPart;
			
			if($oSegment->FARE != '') {
				$iFlightNum++;
				$iPrice += (int) $oSegment->FARE;
			}
		}
		
		foreach($oXML->TICKET->TAXES->TAX as $oTax) {
			$iPrice += (int) $oTax->AMOUNT;
		}
		
		$aVariant['priceDetail'] = array(
				array(
						'totalValue' => $iPrice
				)
		);
		$oVartiant = new Biletoid_Variant($aVariant, array('why do we need this array? T_T'));
		
		$aTicket['oVariant'] = $oVartiant;
		return $aTicket;
	}
	
	private function sendEmailAboutUnknownCode($sType, $sCode) {
		$sEmail = 'В процессе парсинга обнаружен неизвестный код '.$sType.'. Код: '.$sCode.' len = '.strlen($sCode);
		Biletoid_Sender::addEmailJob($this->sErrorsEmail, 'неизвестный код '.$sType, array('html' => $sEmail));
	}
}

?>