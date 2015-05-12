<?php

class musicGenerator {
    
    public function __construct () {
        
        # Chords
        $this->chordArray = array (
            '0'   => array ('36', '52', '55', '60'),
			'1'   => array ('38', '53', '57', '60'),
		);    
    }
    
    public function generateMusic ($numberOfChords) {
        # Generate array with amount of chords necessary
        # This can connect to any music generation routine
        
        $sequenceOfChords = array ();
        for ($i = 1; $i <= $numberOfChords; $i++) {
            $randomChoice = rand (0, (count ($this->chordArray) - 1));
            $sequenceOfChords[] = $this->chordArray[$randomChoice];
        }
        
        return $sequenceOfChords;
    }
    
}


?>