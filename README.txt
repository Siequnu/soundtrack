sountrack is a smart soundtrack generator. The program analyses a given video for scene changes and builds a smart soundtrack around these points. This is my second project using PHP and Git.

Deployment notes:
This script needs write permissions to the /output directory.
The script needs ffmpeg and timidity for media conversion.

Usage indications:
Source video must have the following filepath: /content/video.mp4

After successfully running the program, finalvideo.mpg will be written in the output directory, combining the original video with the new soundtrack.

As this is a proof of concept, the cut points will be marked with random chord. 