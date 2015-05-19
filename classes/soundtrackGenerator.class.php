<?php

class soundtrackGenerator {
    
	
	private $videoFilepath;
	private $audioFilepath;
	private $outputDirectory;
	private $outputFilepath;
	
			
    public function __construct() {
        require_once './classes/midiGenerator.class.php';
        require_once './classes/musicGenerator.class.php';
    }
    
	
	public function getErrorMessage () {return $this->errorMessage;}
	
	
    public function getSoundtrack ($inputVideoLocation) {  
		# Extend time limit from default 30: ffmpeg takes a while for longer video files
        set_time_limit (300);
        
		# Set videoFilepath and check $outputFilepath is writeable.
		if (!$this->setDefaultPaths($inputVideoLocation)) {
			echo "Script could not run, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";die;
		}
		
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
        $pathToMIDIFile = $this->midiGenerator->generateMIDIFile ($sequenceOfChords, $sceneChangeTimings);
        
        # Deal with error messages
        if (!$pathToMIDIFile) {
            echo "\n<p>The MIDI file could not be created, due to the following error: <pre>".htmlspecialchars($this->midiGenerator->getErrorMessage())."</pre></p>";
            return false;
        } 
        
        # Convert MIDI file to WAV and set $this->audioFilepath
		if (!$this->convertMIDIToWAV ($pathToMIDIFile)) {
			echo "\n<p>The audio file could not be created, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";
		}
        
        # Merge generated audio with video and display in browser
		if (!$this->mergeAudioWithVideo($pathToMIDIFile)) {
			echo "\n<p>The audio could not be merged with the video, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";
		} else {
			# Echo HTML5 tag with video file
			$pathToVideoFile = './output/finalvideo.mp4';
			echo $this->getVideoHTMLTag ($pathToVideoFile);	
		}
    }
	
	/*
	 * Set the class properties for default paths
	 *
	 */
	public function setDefaultPaths ($inputVideoLocation) {
		
		$outputFolder = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/';
		$outputFilepath = $outputFolder . 'finalvideo.mp4';
		
		# Set path to original video file
		if (!$this->setVideoFilepath ($inputVideoLocation)) {
			return false;
		}
		
		# Check origin video is readable
		if (!is_readable($this->videoFilepath)) {
			$this->errorMessage = 'Content video is not readable. Check read permissions.';
			return false;
		}
		
		# Set and check output folder is writeable
		if (!$this->setOutputFilepath ($outputFolder, $outputFilepath)) {
			return false;
		}
		
		return true;
	}
	
	/*
	 * Set class property videoFilepath
	 *
	 * @param str $path Path to source video
	 */
	public function setVideoFilepath ($path) {
		$this->videoFilepath = $path;
		if (!is_file ($this->videoFilepath)) {
			$this->errorMessage = 'No source video found.';
			return false;
		}
		return true;
	}
	
	/*
	 * Set class property outputFilepath and outputDirectory
	 *
	 * @param str $outputFolder Path to output folder
	 * @param str $outputFilepath Output filepath
	 */
	public function setOutputFilepath ($outputFolder, $outputFilepath) {
		if (!is_writable ($outputFolder)) {
			$this->errorMessage = "Can't write to output directory.";
			return false;
		}
		$this->outputDirectory = $outputFolder;
		$this->outputFilepath = $outputFilepath;
		return true;
	}
	
	
	/*
     * Generate HTML5 video tag for video at a given location
     *
     * @param str $location Filename (within running directory) of the videofile
     *
     * @return str HTML code
     */ 
    public function getVideoHTMLTag ($location) {
        $html = "<video src=\"{$location}\" width=600 controls=\"controls\">
                Your browser does not support the VIDEO element
                </video>";    
        return $html;
    }
    
	
	/*
	 * Generate an array with cutscene timings
	 *
	 * @return array Array with timings
	 */
    public function getCutScenes () {
		# Define command to be run		
		$cmd = "ffprobe -show_frames -of compact=p=0 -f lavfi \"movie={$this->videoFilepath},select=gt(scene\,0.3)\" > {$this->outputDirectory}scene-changes.txt"; 

		# Execute command
        $exitStatus = $this->execCmd ($cmd);
	
		# Handle errors
		if ($exitStatus != 0) {
            $this->errorMessage = 'The cut scenes could not be extracted due to an error with ffprobe.';  
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
				
		# Parse timings line (from 'pkt_pts_time=2.080000' to '2.080')
		foreach ($explodedRows as $frame) {
			$sceneChangeTime = $frame[3];
			$sceneChangeTime = explode('=', $sceneChangeTime); 
			$sceneChangeTime = $sceneChangeTime[1]; // 2.080000
			$timeSplitDecimalPoint = explode ('.', $sceneChangeTime);
			$strLen = strlen ($timeSplitDecimalPoint[0]); // 2.08000 -> 2080; 19.8788 -> 19878
			$joinedData = implode ($timeSplitDecimalPoint); // 2080
			$finalFormattedTime[] = substr ($joinedData, 0, ($strLen + 3));
		}
		
		return $finalFormattedTime;	
	}
	
	
	/* Set class property audioFilepath
	 *
	 * @param str $path Path to converted audio file
	 *
	 */
	public function setAudioFilepath ($path) {
		$this->audioFilepath = $path;
		if (!is_readable ($this->audioFilepath)) {
			$this->errorMessage = "Can't read converted audio file. File is not present or check read permissions.";
			return false;
		}
		return true;
	}
	
	
	/*
	 * Uses ffmpeg to write new audio on a video
	 */
	public function mergeAudioWithVideo () {
		# Define command
        $cmd = "ffmpeg -y -i \"{$this->audioFilepath}\" -i \"{$this->videoFilepath}\" -preset ultrafast \"{$this->outputFilepath}\"";

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
    public function convertMIDIToWAV ($midiFilepath) {
        # Convert MIDI file to WAV using timidity in shell
        # Define command
		$cmd = "timidity -Ow \"{$midiFilepath}\"";   
        
		# Execute command
		$exitStatus = $this->execCmd ($cmd);
        
		# Deal with error messages
		if ($exitStatus != 0) {
            #echo nl2br (htmlspecialchars (implode ("\n", $output)));
            $this->errorMessage = 'The WAV file could not be created, due to an error with the converter.';  
            return false;
        }
		
		# Set $this->audioFilepath
		$pathToMusicFile = dirname ($_SERVER['SCRIPT_FILENAME']) . '/output/' . pathinfo ($midiFilepath, PATHINFO_FILENAME) . '.wav';
		
		if (!$this->setAudioFilepath ($pathToMusicFile)) {
			$this->errorMessage = 'The converted audio file could not be found.';
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
	 * Executes a command and returns an exit status
	 *
	 * @param str $cmd The command
	 *
	 * @return bool The exit status
	 */
	private function execCmd ($cmd) {
		if (substr(php_uname(), 0, 5) == "Linux"){ 
			exec ($cmd, $output, $exitStatus);
		} else { 
        $cmd = '/usr/local/bin/' . $cmd;
        exec ($cmd, $output, $exitStatus);   
		}		
		return $exitStatus;
	}
	
}

?>