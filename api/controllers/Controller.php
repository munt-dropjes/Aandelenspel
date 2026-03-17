<?php

namespace Controllers;

class Controller
{
    public function ping()
    {
        $this->respond("");
    }

    public function diagnostics(){
        $data = [
            'status' => 'OK',
            'timestamp' => date("Y-m-d H:i:s"),
            'server' => $_ENV['JWT_ISSUER'],
            'routes' => [
                'GET /ping' => 'Controleert of de API bereikbaar is.',
                'GET /diagnostics' => 'Geeft diagnostische informatie over de API.',
                'POST /api/login' => 'Authenticeert een gebruiker en retourneert een JWT.',
                'GET /api/users' => 'Haal alle gebruikers op.',
                'GET /api/users/{id}' => 'Haal een specifieke gebruiker op.',
                'POST /api/users' => 'Maak een nieuwe gebruiker aan.',
                'PUT /api/users/{id}' => 'Werk een bestaande gebruiker bij.',
                'DELETE /api/users/{id}' => 'Verwijder een gebruiker.',
                'GET /api/companies' => 'Haal alle bedrijven op.',
                'GET /api/companies/{id}' => 'Haal een specifiek bedrijf op.',
                'GET /api/tasks' => 'Haal alle taken op.',
                'POST /api/tasks/complete' => 'Markeer een taak als voltooid.',
                'POST /api/tasks/import' => 'Importeer taken vanuit een CSV-bestand.',
                'DELETE /api/tasks/all' => 'Verwijder alle taken.',
                'GET /api/history/{dateTime}' => 'Haal de geschiedenis op sinds een bepaald tijdstip.',
                'POST /api/history/save' => 'Sla de huidige geschiedenis op.',
                'GET /api/transactions' => 'Haal de transactiegeschiedenis op.',
                'POST /api/transactions' => 'Maak een nieuwe transactie aan.',
                'POST /api/transactions/transfer' => 'Voer een geldtransfer uit tussen gebruikers.',
                'GET /api/stocks' => 'Haal alle beschikbare aandelen op.',
                'GET /api/stocks/bank' => 'Haal alle aandelen van de bank op.',
                'GET /api/stocks/company/{companyId}' => 'Haal alle aandelen van een specifiek bedrijf op.',
                'POST /api/stocks/trade' => 'Voer een aandelenhandel uit.',
                'POST /api/offers' => 'Maak een nieuw handelsaanbod.',
                'GET /api/offers/pending' => 'Haal alle openstaande handelsaanbiedingen op.',
                'POST /api/offers/{id}/accept' => 'Accepteer een handelsaanbod.',
                'POST /api/offers/{id}/decline' => 'Weiger een handelsaanbod.',
                'GET /api/game/settings' => 'Haal de huidige spelinstellingen op.',
                'POST /api/game/start' => 'Start een nieuw spel.',  
                'POST /api/game/reset' => 'Reset het spel naar de beginstatus.',
            ]
        ];
        $this->respond($data);
    }

    protected function respond($data)  : void
    {
        $this->respondWithCode(200, $data);
    }

    protected function respondWithError($httpCode, $message) : void
    {
        $data = array('errorMessage' => $message);
        $this->respondWithCode($httpCode, $data);
    }

    private function respondWithCode($httpCode, $data) : void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpCode);
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    function requestObjectFromPostedJson($className)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->respondWithError(400, "Ongeldige JSON aangeleverd.");
        }

        $object = new $className();
        foreach ($data as $key => $value) {
            if(is_object($value)) {
                continue;
            }
            $object->{$key} = $value;
        }
        return $object;
    }
}
