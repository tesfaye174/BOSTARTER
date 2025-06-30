<?php
namespace BOSTARTER\Utils;
class BaseController {
    protected $connessioneDatabase;
    protected $logger;
    public function __construct() {
        $this->connessioneDatabase = \Database::getInstance()->getConnection();
        $this->logger = new \BOSTARTER\Services\MongoLogger();
    }
    protected function rispostaStandardizzata($successo, $messaggio, $dati = null, $errori = null) {
        $risposta = [
            'success' => (bool)$successo,
            'message' => $messaggio,
            'errors' => []
        ];
        if ($dati !== null) {
            $risposta['data'] = $dati;
        }
        if ($errori !== null) {
            $risposta['errors'] = is_array($errori) ? $errori : [$errori];
        }
        return $risposta;
    }
    protected function gestisciErrore(\Exception $errore, $operazione, $messaggioUtente = null) {
        error_log("Errore in {$operazione}: " . $errore->getMessage());
        if ($this->logger) {
            $this->logger->registraErrore("Errore {$operazione}", [
                'messaggio' => $errore->getMessage(),
                'file' => $errore->getFile(),
                'linea' => $errore->getLine(),
                'trace' => $errore->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        $messaggioFinale = $messaggioUtente ?? 'Si è verificato un problema. Riprova più tardi.';
        return $this->rispostaStandardizzata(false, $messaggioFinale);
    }
    protected function validaParametri($parametriRichiesti, $datiRicevuti) {
        $errori = [];
        foreach ($parametriRichiesti as $parametro) {
            if (!isset($datiRicevuti[$parametro]) || empty($datiRicevuti[$parametro])) {
                $errori[] = "Il parametro '{$parametro}' è obbligatorio";
            }
        }
        return empty($errori) ? true : $errori;
    }
    protected function calcolaPaginazione($paginaCorrente, $elementiPerPagina, $totaleElementi) {
        $totalePagine = ceil($totaleElementi / $elementiPerPagina);
        $offset = ($paginaCorrente - 1) * $elementiPerPagina;
        return [
            'pagina_corrente' => $paginaCorrente,
            'elementi_per_pagina' => $elementiPerPagina,
            'totale_elementi' => $totaleElementi,
            'totale_pagine' => $totalePagine,
            'offset' => $offset,
            'ha_pagina_precedente' => $paginaCorrente > 1,
            'ha_pagina_successiva' => $paginaCorrente < $totalePagine
        ];
    }
}
