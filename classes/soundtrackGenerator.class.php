<?php

class soundtrackGenerator {
    
    public function __construct() {
        require_once './classes/midiGenerator.class.php';
        require_once './classes/musicGenerator.class.php';
    }
    
	public function getErrorMessage () {return $this->errorMessage;}
	
    public function getSoundtrack () { 
        
        set_time_limit (120);
        
        # Get cutscene timings
        $sceneChangeTimings = $this->getCutScenes();
        
		if (!$sceneChangeTimings)  {
			echo "Cut scene creation failed, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>"; die;
		}
		
        # Generate music for these cutscenes
        $this->musicGenerator = new musicGenerator;
        $numberOfTracks = count($sceneChangeTimings);
        $sequenceOfChords = $this->musicGenerator->generateMusic($numberOfTracks);
        
        # Send array to MIDI Generator
        $this->midiGenerator = new midiGenerator;
        $file = $this->midiGenerator->generateMIDIHarmony ($sequenceOfChords, $sceneChangeTimings);
        
        # Deal with result messages
        if (!$file) {
            echo "\n<p>The MIDI file could not be created, due to the following error: <pre>".htmlspecialchars($this->midiGenerator->getErrorMessage())."</pre></p>";
            return false;
        } 
        
        # Convert MIDI file to WAV
        $this->convertMIDIToWAV ($file);
        
        # Echo HTML5 tag with converted WAV file
        #$pathToMusicFile = '/soundofcolour/output/' . pathinfo ($file, PATHINFO_FILENAME) . '.wav';
        #echo $this->getAudioHTMLTag ($pathToMusicFile);
        
        # Join video with generated audio
        $pathToMusicFile = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/' . pathinfo ($file, PATHINFO_FILENAME) . '.wav';
        $pathToMovieFile = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/video.mp4';
        $outputFilepath = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/finalvideo.avi';

        $cmd = "/usr/local/bin/ffmpeg -y -i \"{$pathToMusicFile}\" -i \"{$pathToMovieFile}\" \"{$outputFilepath}\"";
        
		$exitStatus = $this->execCmd ($cmd);
		
		if ($exitStatus != 0) {
            $this->errorMessage = 'The video file could not be rendered, due to an error with ffmpeg.';
			echo "\n<p>The MIDI file could not be created, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";
            return false;
        }
		
        echo "Soundtrack succesfully merged with video.";
        
    }
   
    
    
    /*
     * Converts a MIDI file to WAV.
     *
     * @param str $file Filepath of MIDI file to be converted
     *
     * @return bool True if operation succeded, False if error occured.
     */ 
    public function convertMIDIToWAV ($file) {
        # Convert MIDI file to WAV using timidity in shell
        $cmd = "/usr/local/bin/timidity -Ow \"{$file}\"";   
        
		$exitStatus = $this->execCmd ($cmd);
        
		if ($exitStatus != 0) {
            #echo nl2br (htmlspecialchars (implode ("\n", $output)));
            $this->errorMessage = 'The WAV file could not be created, due to an error with the converter.';  
            return false;
        }
        return true;
    }
    
    
    /*
     * Generate HTML5 audio tag for audio at a given location
     *
     * @param str $location Filename (within running directory) of the audiofile
     *
     * @return str HTML code
     */ 
    public function getAudioHTMLTag ($location) {
        
        $html = "<audio src=\"{$location}\" controls=\"controls\">
                Your browser does not support the AUDIO element
                </audio>";    
        return $html;
    }
    
	
	/*
	 * Generate an array with cutscene timings
	 *
	 * @return array Array with timings
	 */
    public function getCutScenes () {
		# Get cut scenes
		$videoName = 'video.mp4';
		$directory  = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/' . $videoName;
        $outputDirectory = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/';
				
		$cmd = "/usr/local/bin/ffprobe -show_frames -of compact=p=0 -f lavfi \"movie=\"{$directory}\",select=gt(scene\,0.4)\" > \"{$outputDirectory}\"scene-changes.txt"; 
        
        $exitStatus = $this->execCmd ($cmd);
	
		if ($exitStatus != 0) {
            $this->errorMessage = 'The cut scenes could not be extracted due to an error with ffprobe';  
            return false;
        }
		
		$sceneChangeFileLocation = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/scene-changes.txt';
		$txt_file = file_get_contents($sceneChangeFileLocation);
		$rows = explode("\n", $txt_file);
		
		foreach ($rows as $frameInfo) {
			$data[] = explode('|', $frameInfo);
		}
		
		# Remove last element (empty line)
		array_pop($data);
				
		$sceneChangeTime = array ();
		foreach ($data as $frame) {
			$sceneChangeTime[] = $frame[3];
		}
		unset ($data);
		foreach ($sceneChangeTime as $time) {
			$data[] = explode('=', $time);
		}
		
		unset ($sceneChangeTime);
		
		foreach ($data as $time) {
			$sceneChangeTime[] = $time[1];
		}
		unset ($data);
		foreach ($sceneChangeTime as $time) {
			$data[] = explode ('.', $time);
		}
		$finalFormattedTime = array ();
		foreach ($data as $line) {
				if (strlen ($line[0]) == 1) {
					$joinedData = implode ($line);
					$finalFormattedTime[] = substr ($joinedData, 0, 4);	
				}
				if (strlen ($line[0]) == 2) {
					$joinedData = implode ($line);
					$finalFormattedTime[] = substr ($joinedData, 0, 5);	
				}
				if (strlen ($line[0]) == 3) {
					$joinedData = implode ($line);
					$finalFormattedTime[] = substr ($joinedData, 0, 6);	
				}
		}
		return $finalFormattedTime;
	}
	
	
	/*
	 * Executes a command and returns an exit status
	 *
	 * @param str $cmd The command
	 *
	 * @return bool The exit status
	 */
	private function execCmd ($cmd) {
		if (substr(php_uname(), 0, 7) == "Windows"){ 
			pclose(popen("start /B ". $cmd, "r"));  
		} else { 
        exec ($cmd, $output, $exitStatus);   
		}		
		return $exitStatus;
	}
	
}

?>