<?php

class soundtrack {
    
	public $videoID;
    public $inputVideoLocation;
	
	
    public function __construct () {
        include_once './classes/soundtrackGenerator.class.php';        
    }
    
	
	public function getErrorMessage () {return $this->errorMessage;}
    
	
    public function main () {        
        # Initialize soundtrackGenerator
		$this->soundtrackGenerator = new soundtrackGenerator;
		
		# Generate form to get link to youtube video
        
        $formData = $this->generateForm();
		
		
		# Process submitted URL
		if ($formData) {
			# Retrieve and assign form data and set videoID
			$this->assignFormData($formData);
			
			# Set input video location
			$this->inputVideoLocation = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/' . $this->videoID . '-video.mp4';
			
			# Build URL and path to target video file
			$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/lib/getvideo/getvideo.php?videoid=' . $this->videoID . '&format=ipad';	
			$filetarget = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/';
		
			# Download video and deal with errors 
			if (!$this->downloadVideo($url, $filetarget)) {
				echo "Video could not be downloaded, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";   
			};
			
			# Send video to soundtrack generator
			$this->soundtrackGenerator->getSoundtrack($this->inputVideoLocation);
		}
		
    }
    
    
	public function assignFormData($formData) {
		if (!empty($formData['url'])) {
			$this->videoID = $formData['url'];
			$this->formSubmitted = true;
			$this->soundtrackGenerator->videoID = $this->videoID;
		}	
	}
	
    public function downloadVideo($url, $file_target) {  
        # Check if target directory is writeable
		if (!is_writeable($file_target)) {
			$this->errorMessage = 'Can not write to output directory.';
			return false;
		}
		
		$file_target = 'content/' . $this->videoID . '-video.mp4';
		
		# Download file
		$cmd = "curl -L -o {$file_target} '{$url}'";
		exec ($cmd, $output, $exitStatus);
		if (!$exitStatus === 0) {
			$this->errorMessage = 'Download failed due to an error with cURL.';
			return false;
		}
        return true;
    }
    
    
    public function generateForm () {
        # Load the form module 
        require_once ('./lib/ultimateform/ultimateForm.php');
        require_once ('./lib/ultimateform/pureContent.php');
        require_once ('./lib/ultimateform/application.php');
        
        # Create a form instance 
        $form = new form (array (
            'div'                    => 'form-download',
            'submitButtonText'       => 'Submit URL',
			'formCompleteText'       => false,
			'requiredFieldIndicator' => false,
			'submitButtonAccesskey'  => false,
        ));
        
        $form->heading (2, 'YouTube Video Soundtrack Creation');
        $form->heading ('p', 'Please enter the video ID in the box below.');
        
        # Create a standard input box
        $form->input (array (
        'name'					=> 'url',
        'title'					=> 'YouTube URL',
        'description'			=> '',
        'output'				=> array (),
        'size'					=> 32,
        'maxlength'				=> '',
        'default'				=> 'P6JfInyQI9Q',
        'regexp'				=> '',
        ));
        
		# Process form and return result
        $result = $form->process ();
		return $result;
    }
    
}

?>