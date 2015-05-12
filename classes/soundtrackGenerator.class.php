<?php

class soundtrackGenerator {
    
    public function __construct() {
        require_once './classes/midiGenerator.class.php';
        require_once './classes/musicGenerator.class.php';
    }
    
	public function getErrorMessage () {return $this->errorMessage;}
	
    public function getSoundtrack () { 
        
		# Extend time limit from default 30: ffmpeg takes a while for longer video files
        set_time_limit (120);
        
        # Get array with cutscene timings from video
        $sceneChangeTimings = $this->getCutScenes();
        
		# Deal with errors
		if (!$sceneChangeTimings)  {
			echo "Cut scene creation failed, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>"; die;
		}
		
        # Generate enough chords for the amount of cutscenes
        $this->musicGenerator = new musicGenerator;
		$numberOfTracks = count($sceneChangeTimings);
        $sequenceOfChords = $this->musicGenerator->generateMusic($numberOfTracks);
        
        # Send chord array and cutscene timings to MIDI Generator
        $this->midiGenerator = new midiGenerator;
        $pathToAudioFile = $this->midiGenerator->generateMIDIFile ($sequenceOfChords, $sceneChangeTimings);
        
        # Deal with error messages
        if (!$pathToAudioFile) {
            echo "\n<p>The MIDI file could not be created, due to the following error: <pre>".htmlspecialchars($this->midiGenerator->getErrorMessage())."</pre></p>";
            return false;
        } 
        
        # Convert MIDI file to WAV
        $success = $this->convertMIDIToWAV ($pathToAudioFile);
		if (!$success) {
			echo "\n<p>The MIDI file could not be created, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";
		}
        
        # Echo HTML5 tag with converted WAV file
        #$pathToMusicFile = '/soundofcolour/output/' . pathinfo ($file, PATHINFO_FILENAME) . '.wav';
        #echo $this->getAudioHTMLTag ($pathToMusicFile);
        
        # Merge generated audio with video
        $success = $this->mergeAudioWithVideo($pathToAudioFile);
		if (!$success) {
			echo "\n<p>The audio could not be merged with the video, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";
		}
		
		# Confirm file was created
        echo "Soundtrack succesfully merged with video.";
        
    }
	
	
	/*
	 * Uses ffmpeg to write new audio on a video
	 */
	public function mergeAudioWithVideo ($pathToAudioFile) {
		# Define paths
        $pathToMusicFile = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/' . pathinfo ($pathToAudioFile, PATHINFO_FILENAME) . '.wav';
        $pathToMovieFile = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/video.mp4';
        $outputFilepath = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/finalvideo.avi';

		# Define command
        $cmd = "/usr/local/bin/ffmpeg -y -i \"{$pathToMusicFile}\" -i \"{$pathToMovieFile}\" \"{$outputFilepath}\"";
        
		# Execute command
		$exitStatus = $this->execCmd ($cmd);
		
		# Deal with error messages
		if ($exitStatus != 0) {
            $this->errorMessage = 'The video file could not be rendered, due to an error with ffmpeg.';
            return false;
        }
		
		return true;
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
        # Define command
		$cmd = "/usr/local/bin/timidity -Ow \"{$file}\"";   
        
		# Execute command
		$exitStatus = $this->execCmd ($cmd);
        
		# Deal with error messages
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
		# Set variables for location
		$videoName = 'video.mp4';
		$directory  = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/' . $videoName;
        $outputDirectory = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/';
			
		# Define command to be run		
		$cmd = "/usr/local/bin/ffprobe -show_frames -of compact=p=0 -f lavfi \"movie=\"{$directory}\",select=gt(scene\,0.4)\" > \"{$outputDirectory}\"scene-changes.txt"; 
        
		# Execute command
        $exitStatus = $this->execCmd ($cmd);
	
		# Handle errors
		if ($exitStatus != 0) {
            $this->errorMessage = 'The cut scenes could not be extracted due to an error with ffprobe';  
            return false;
        }
		
		# Parse and return cut scene file
		$sceneChangeFileLocation = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/scene-changes.txt';
		return $this->parseCutSceneFile($sceneChangeFileLocation);
		
	}
	
	
	/*
	 * Parse an output file from ffprobe to get timings
	 *
	 * @param str $filepath Path to ffprobe output file
	 *
	 * @return array Array with timings
	 */
	public function parseCutSceneFile ($filepath) {
		
		# Set location
		$txt_file = file_get_contents($filepath);
		
		# Parse into rows
		$rows = explode("\n", $txt_file);
		foreach ($rows as $frameInfo) {
			$explodedRows[] = explode('|', $frameInfo);
		}
		
		# Remove last element (contains no timing information)
		array_pop($explodedRows);
				
		# Parse timings line (from 'pkt_pts_time=2.080000' to '2080')
		$sceneChangeTime = array ();
		foreach ($explodedRows as $frame) {
			$sceneChangeTime[] = $frame[3];
		}
		
		foreach ($sceneChangeTime as $time) {
			$timeWithHeader[] = explode('=', $time);
		}
				
		foreach ($timeWithHeader as $time) {
			$timeNoHeader[] = $time[1];
		}
		
		foreach ($timeNoHeader as $time) {
			$timeSplitDecimalPoint[] = explode ('.', $time);
		}
		
		$finalFormattedTime = array ();
		
		# Join the values before and after the decimal point
		foreach ($timeSplitDecimalPoint as $line) {
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