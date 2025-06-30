<?php
class RedundancyAnalysis {
    private $wI = 1;    
    private $wB = 0.5;  
    private $a = 2;     
    private $progetti = 10;
    private $finanziamenti_per_progetto = 3;
    private $utenti = 5;
    private $progetti_per_utente = 2;
    public function analizza() {
        $op1_freq = 1;
        $op1_peso = $this->wI;
        $op2_freq = 1; 
        $op2_peso = $this->wB;
        $op3_freq = 3;
        $op3_peso = $this->wB;
        $costo_senza = ($op1_freq * $op1_peso * $this->a) + 
                       ($op2_freq * $op2_peso * ($this->progetti + $this->progetti * $this->finanziamenti_per_progetto)) +
                       ($op3_freq * $op3_peso * $this->progetti_per_utente);
        $costo_con = ($op1_freq * $op1_peso * ($this->a + 1)) + 
                     ($op2_freq * $op2_peso * ($this->progetti + $this->progetti * $this->finanziamenti_per_progetto)) +
                     ($op3_freq * $op3_peso * 1); 
        return [
            "senza_ridondanza" => $costo_senza,
            "con_ridondanza" => $costo_con,
            "raccomandazione" => $costo_con < $costo_senza ? "MANTENERE" : "ELIMINARE",
            "dettagli" => [
                "wI" => $this->wI,
                "wB" => $this->wB, 
                "a" => $this->a,
                "volumi" => [
                    "progetti" => $this->progetti,
                    "utenti" => $this->utenti,
                    "progetti_per_utente" => $this->progetti_per_utente
                ]
            ]
        ];
    }
}
?>
