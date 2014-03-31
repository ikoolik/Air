<?php

use Tickets\Parser;

require_once '../bootstrap.php';

echo '<pre>';
$booking = Parser::getTicketFromFile('../sup/tickets/AAAHLGAL.MIR', Parser::PARSER_MIR);
var_dump($booking);
