soundtrack is a smart soundtrack generator. The program analyses a YouTube video for scene changes and builds a smart soundtrack around these points. This is my second project using PHP and Git.

Deployment notes:
This script needs read and write permissions to the /output and /content directories.
This script needs ffmpeg and timidity for media conversion (eg. $ brew install timidity). For web server use there are fallback 32bit static binaries included in the main folder, which the program will try to use if the system ffmpeg and ffprobe fail.

Usage indications:
Main entry to program: index.php. 
To generate a cut-point chords, submit the video ID of any YouTube video. Processing will take some time, depending on the length of the source video.

After successfully running the program, .mp4 video will be written in the /output directory. The browser will attempt to play back the content created.

As this is a proof of concept, the cut points will be marked with random chord. 

Known issues:
Monetised YouTube videos might attempt to show an ad at the start. In this case, the script will be unable to process the requested video. Try another video.