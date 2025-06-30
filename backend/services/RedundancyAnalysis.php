<?php
class RedundancyAnalysis {
    private $wI = 1, $wB = 0.5, $a = 2;
    private $progetti = 10, $finanziamenti_per_progetto = 3, $utenti = 5, $progetti_per_utente = 2;
    public function analyze() {
        $con_ridondanza = ($this->wI * 1 * 1) + ($this->wB * ($this->progetti + $this->progetti * $this->finanziamenti_per_progetto) * 1) + ($this->wB * 1 * 3);
        $senza_ridondanza = ($this->wI * 1 * 1) + ($this->wB * ($this->progetti + $this->progetti * $this->finanziamenti_per_progetto) * 1) + ($this->wB * $this->progetti_per_utente * 3);
        return $con_ridondanza < $senza_ridondanza ? "MANTENERE ridondanza" : "ELIMINARE ridondanza";
    }
}
?>
