<?php

class soundtrack {
    
	public $formSubmitted;
	public $videoID;
    public $inputVideoLocation;
	
	
    public function __construct () {
        include_once './classes/soundtrackGenerator.class.php';        
    }
    
	
	public function getErrorMessage () {return $this->errorMessage;}
    
	
    public function main () {        
        # Set input video location
        $this->inputVideoLocation = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/video.mp4';
                
        # Generate form to get link to youtube video
        if (!$this->formSubmitted) {
            $formData = $this->generateForm();
		}

		# Retrieve and assign form data and set videoID
		$this->assignFormData($formData);
		
		# Process submitted URL
		if ($this->formSubmitted) {
			# Build URL and path to target video file
			$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/lib/getvideo/getvideo.php?videoid=' . $this->videoID . '&format=ipad';	
			$filetarget = dirname ($_SERVER['SCRIPT_FILENAME']) . '/content/video.mp4';
	
			# Download video and deal with errors 
			if (!$this->downloadVideo($url, $filetarget)) {
				echo "Video could not be downloaded, due to the following error: <pre>".htmlspecialchars($this->getErrorMessage())."</pre></p>";   
			};
			
			# Send video to soundtrack generator
			$soundtrackGenerator = new soundtrackGenerator;
			$soundtrackGenerator->getSoundtrack($this->inputVideoLocation);
		}
    }
    
    
	public function assignFormData($formData) {
		if (!empty($formData['url'])) {
			$this->videoID = $formData['url'];
			$this->formSubmitted = true;
		}	
	}
	
    public function downloadVideo($url, $file_target) {  
        # Check for read/write permission for URL and target file
        if (!$rh = fopen($url, 'rb')) {
            $this->errorMessage = "Can not read origin url. \nIf HTTP/1.1 403 Forbidden error was shown, YouTube might be trying to display an Ad before the video. \nTry again with a different video.";
            return false;
        };
        
        if (!$wh = fopen($file_target, 'wb')) {
            $this->errorMessage = 'Can not write video to target folder.';
            return false;   
        };
        
        while (!feof($rh)) {
            if (fwrite($wh, fread($rh, 1024)) === FALSE) {
                   $this->errorMessage = 'Download error: Cannot write to file ('.$file_target.')';
                   return false;
               }
        }
        
        fclose($rh);
        fclose($wh);
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