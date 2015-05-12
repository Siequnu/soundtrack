<?php

class musicGenerator {
    
    public function __construct () {
        
        # Possible chords, can be extended to any type of GM1 input
        $this->chordArray = array (
            '0'   => array ('36', '52', '55', '60'),
			'1'   => array ('38', '53', '57', '60'),
			'2'   => array ('43', '55', '59', '62'),
			'3'   => array ('36', '55', '60', '64'),
		);    
    }
    
	
	/*
	 * Generates a sequence of random chords
	 *
	 * @param int $numberOfChords Number of chords to generate
	 *
	 * @return array Array with chords and keyboard positions
	 */
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