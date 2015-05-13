soundtrack is a smart soundtrack generator. The program analyses a YouTube video for scene changes and builds a smart soundtrack around these points. This is my second project using PHP and Git.

Deployment notes:
This script needs read and write permissions to the /output and /content directories.
This script needs ffmpeg and timidity for media conversion.

Usage indications:
Main entry to program: index.php. Submit a YouTube video URL in the given form.

After successfully running the program, finalvideo.mp4 will be written in the /output directory, combining the original video with the new soundtrack. The browser will attempt to play back the video. 

As this is a proof of concept, the cut points will be marked with random chord. 