<?php

namespace Tickets;

class Parser {
    const PARSER_MIR = 'Mir';
    const PARSER_AIR = 'Air';
    const PARSER_SIRENA = 'Sirena';

    public static function getTicketFromFile($file, $parserType) {
        if(!in_array($parserType, [self::PARSER_AIR, self::PARSER_MIR, self::PARSER_SIRENA])) {
            throw new ParsingException("Parser '{$parserType}' does not exists");
        }

        if(!file_exists($file)) {
            throw new ParsingException("File not found. {$file}");
        }

        $class = "Tickets\\Parser\\$parserType";
        $parser = new $class($file);
        return $parser->getTicket();
    }

    public static function parseDirectory($dir, $type) {
        if(is_dir($dir)) {
            $tickets = [];
            $handler = opendir($dir);
            while ($file = readdir($handler)) {
                if(!in_array($file, ['.', '..'])) {
                    $ticket = self::getTicketFromFile($dir.DIRECTORY_SEPARATOR.$file, $type);

                    //FIXME: приведение к boolean
                    if($ticket) {
                        $tickets[] = $ticket;
                    }
                }
            }
            return $tickets;
        } else {
            throw new ParsingException("No such directory '{$dir}'");
        }
    }
}


class ParsingException extends \Exception {}