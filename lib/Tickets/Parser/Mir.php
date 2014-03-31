<?php

namespace Tickets\Parser;


use Tickets\Base\Parser;
use Tickets\Elements\Passenger;

class Mir extends Parser {

    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }
    public function getTicket()
    {
        $contents = file_get_contents($this->file);
        $sections = explode("\r\r",$contents);

        //последняя секция - указатель на конец файла нахер нам не нужна
        $sections = array_slice($sections, 0, (count($sections) - 1));

        $parsingResults = [];
        $code = 'Header'; //идентификатор начальной секции
        foreach($sections as $section) {
            //проверяем, есть ли у прверяемой секции идентификатор
            if(preg_match("#^A\d\d#", $section, $match)) {
                $code = $match[0];
            }

            $parsingResults[$code] = $this->parseSection($section, $code);
        }
        return $parsingResults;
    }

    public function parseSection($text, $code)
    {
        $method = 'parse'.$code.'Section';

        if(method_exists($this, $method)) {
            $section = $this->$method($text);
        } else {
            $section = false;
        }
        return $section;
    }

    private function parseHeaderSection($text) {
        $aSectionData = array(
            'id' => substr($text, 0, 2),
            'transmitting_system' => substr($text, 2, 4),
            'IATANumCode' => substr($text, 4, 4),
            'MIRType' => substr($text, 8, 10),
            'size' => substr($text, 10, 5),
            'created' => $this->parseDateTime(substr($text, 20, 12)),
            'booking_agency' => substr($text, 81, 4),
            'ticketing_agency' => substr($text, 85, 4),
            'agency_acc_num' => substr($text, 89, 9),
            'pnr' => substr($text, 98, 6)
        );
        return $aSectionData;
    }

    private function parseA02Section($text) {

        $passenger = new Passenger();

        $fullNameString = substr($text, 3, 33);
        $nameParts = explode("/",$fullNameString);

        $passenger->setLastName($nameParts[0]);

        $nameParts[1] = trim($nameParts[1]);
        if(preg_match("/MR$/",$nameParts[1])) {
            $passenger->setFirstName(substr($nameParts[1], 0, (strlen($nameParts[1]) - 2)));
            $passenger->setSex(Passenger::SEX_MALE);
        } else {
            $passenger->setFirstName(substr($nameParts[1], 0, (strlen($nameParts[1]) - 3)));
            $passenger->setSex(Passenger::SEX_FEMALE);
        }
        $passenger->iataCode(trim(substr($text, 69, 6)));

        $aSectionData = array(
            'passenger' => $passenger,
            'TCN' => substr($text, 36, 11),
            'Ticket/Invoice' => substr($text, 47, 22),
            'Year' => substr($text, 47, 1),
            'TKT' => substr($text, 48, 10),
            'TicketNum' => substr($text, 58, 2),
            'InvoiceNum' => substr($text, 60, 9),
            'FIN' => substr($text, 75, 2),
            'EIN' => substr($text, 77, 2),
            'FFN' => substr($text, 79, 1)
        );
        return $aSectionData;
    }

    private function parseA04Section($text) {
        $lines = explode("\r",$text);
        $results = [];
        foreach($lines as $line) {
            $results[] = [
                'ItineraryIndex' => substr($line, 3, 2),
                'AirlineCode' => substr($line, 5, 2),
                'AirlineNum' => substr($line, 7, 3),
                'AirineName' => substr($line, 10, 12),
                'FlightNum' => substr($line, 22, 4),
                'Class' => substr($line, 26, 2),
                'Status' => substr($line, 28, 2),
                'DepDate' => substr($line, 30, 5),
                'DepTime' => substr($line, 35, 5),
                'ArrTime' => substr($line, 40, 5),
                'NextDayArrival' => substr($line, 45, 1),
                'DepCityCode' => substr($line, 46, 3),
                'DepCityName' => substr($line, 49, 13),
                'ArrCityCode' => substr($line, 62, 3),
                'ArrCityName' => substr($line, 65, 13),
                'International' => substr($line, 78, 1),
                'SeatIndicator' => substr($line, 79, 1),
                'MealCodes' => substr($line, 80, 4),
                'StopoverIndicators' => substr($line, 84, 1),
                'StopsNum' => substr($line, 85, 1),
                'Baggage' => substr($line, 86, 3),
                'Aircraft' => substr($line, 89, 4),
                'DepTerminal' => substr($line, 93, 3),
                'NauticalMiles' => substr($line, 96, 5),
                'FlightCouponIndicator' => substr($line, 101, 1),
                'SegmentIdentifier' => substr($line, 102, 1)
            ];
        }
        return $results;
    }

    private function parseA07Section($text) {
        $lines = explode("\r", $text);
        $line = current($lines);
        $result = array(
            'FareSectionId' => substr($line, 3, 2),
            'CurrencyCode' => substr($line, 5, 3),
            'BaseFare' => substr($line, 8, 12),
            'CurrencyCodeForFull' => substr($line, 20, 3),
            'FullFare' => substr($line, 23, 12),
            'CurrCodeForEquivAmount' => substr($line, 35, 3),
            'EquivAmount' => substr($line, 38, 12),
        );

        $additional = substr($line, 50);
        if(substr($additional, 0, 3) == 'NR:') {
            $result['NetRemitAmount'] = substr($additional, 3, 8);
            $result['TaxCurrencyCode'] = substr($additional, 11, 3);
            $taxes = substr($additional, 14);
        } else {
            $result['TaxCurrencyCode'] = substr($additional, 0, 3);
            $taxes = substr($additional, 3);
        }
        for($i = 0, $taxCount = (strlen($taxes) / 13); $i < $taxCount; $i++) {
            $result['taxes']['T'.($i + 1)]['value'] = substr($taxes, ($i * 13) + 3, 8);
            $result['taxes']['T'.($i + 1)]['code'] = substr($taxes, ($i * 13) + 11, 2);

        }
        return $result;
    }

    private function parseA08Section($text) {
        $lines = explode("\r",$text);
        $result = [];
        foreach($lines as $line) {
            $result[] = [
                'FareSectionId' => substr($line, 3, 2),
                'ItineraryIndNum' => substr($line, 5, 2),
                'FareBasisCode' => substr($line, 7, 8),
                'SegmentValue' => substr($line, 15, 8),
                'NotValidBefore' => substr($line, 23, 7),
                'NotValidAfter' => substr($line, 30, 7),
                'SegmentTicketDesignator' => substr($line, 37, 6),
                'aAdditionalParams' => $this->parseOptionalDataString(substr($line, 43), 1)
            ];
        }
        return $result;
    }

    private function parseA09Section($sSectionText) {
        $sText = $sSectionText;
        $aSectionData = array(
            'FareSectionId' => substr($sText, 3, 2),
            'Type' => substr($sText, 5, 1),
        );
        return $aSectionData;
    }

    private function parseA11Section($sSectionText) {
        $sText = $sSectionText;
        $aSectionData = array(
            'PaymentType' => substr($sText, 3, 2),
            'PartyAmount' => substr($sText, 5, 12),
            'RefundIndicator' => substr($sText, 17, 1),
            'CardCode' => substr($sText, 18, 2),
            'CardNumber' => substr($sText, 20, 20),
            'CardExpiration' => substr($sText, 40, 4),
            'CardAppCode' => substr($sText, 44, 8),
            'CardAppCodeIndicator' => substr($sText, 45, 1),
            'ActualAppCode' => substr($sText, 46, 8),
            'PaymentPlanOpts' => substr($sText, 52, 3),
            'OptParams' => $this->parseOptionalDataString(substr($sText, 55),1)
        );
        return $aSectionData;
    }

    private function parseA12Section($sSectionText) {
        $aSectionLines = explode("\r",$sSectionText);
        $aSectionData = array();
        foreach($aSectionLines as $sText) {
            $aSectionData[] = array(
                'CityCode' => substr($sText, 3, 3),
                'LocationType' => substr($sText, 6, 2),
                'FreeformPhoneData' => substr($sText, 8, 64),
            );
        }
        return $aSectionData;
    }

    private function parseA14Section($sSectionText) {
        $sText = $sSectionText;
        $aSectionData = array(
            'FreeformRemarks' => substr($sText, 3, 64),
        );
        return $aSectionData;
    }

    private function parseOptionalDataString($sString, $iKeyLenght) {
        //чтобы не терять значение последнего параметра - прибавляем в конец фиктивный индекс без значения
        $sString .= str_repeat("x", $iKeyLenght) .":";

        //регулярка
        $sPattern = "/
			(.{".$iKeyLenght."}:)	#этот кусок ловит идентификатор
			([^:]+) 				#этот кусок отлавливает значение
			(?=.{".$iKeyLenght."}:)	#этот кусок ограничивает значение справа
		/xi";
        preg_match_all($sPattern,$sString, $aMatches);
        //формируем массив значений параметров
        $aOptionParams = array();
        foreach($aMatches[1] as $iMatch => $sKey) { //бежим по найденным индексам
            if(!isset($aOptionParams[$sKey])) {
                $aOptionParams[$sKey] = $aMatches[2][$iMatch];
            } else { //редкий случай наличия двух значений с одним индексом
                $aOptionParams[$sKey] .= ';'.$aMatches[2][$iMatch];
            }
        }
        return $aOptionParams;
    }

    // Утилитарные методы
    private function parseDateTime($text)
    {
        $day = substr($text, 0, 2);
        $month = substr($text, 2, 3);
        $year = substr($text, 5, 2);
        $hours = substr($text, 7, 2);
        $minutes = substr($text, 9, 2);

        $timestamp = strtotime("{$day}-{$month}-{$year} {$hours}:{$minutes}");
        return new \DateTime(date('Y-m-d H:i:s', $timestamp));
    }
} 