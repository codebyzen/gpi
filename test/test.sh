# ffmpeg \
# -i text.jpg \
# -stream_loop 6 \
# -i video.mp4 \
# -i logo.png \
# -i audio.mp3 \
# -hide_banner \
# -timelimit 180 \
# -loglevel error \
# -filter_complex "[0:v]scale=iw:ih[text]; [1:v]scale=640:248[main]; [text][main]overlay=0:50[main]; [2:v]scale=iw:ih[watermark]; [main][watermark]overlay=main_w/4*3-overlay_w/4*3:main_h/5*4-overlay_h" \
# -t 20 \
# -c:v libx264 \
# -preset fast \
# -crf 24 \
# -threads 0 \
# -y out.mp4



ffmpeg \
-timelimit 840 \
-i /Users/ugputu/Work/dsda/gpi_v2//upload/temp/t_1550262112_86_65.jpg \
-stream_loop 8 \
-i /Users/ugputu/Work/dsda/gpi_v2//upload/temp/video123.mp4 \
-i /Users/ugputu/Work/dsda/gpi_v2/assets/images/logo.png \
-i /Users/ugputu/Work/dsda/gpi_v2//upload/temp/audio123.mp3 \
-filter_complex \
" \
	[0:v]scale=iw:ih[text]; \
	[1:v]scale=640:248[video]; \
	[2:v]scale=iw:ih[watermark]; \

	[video][watermark]overlay=main_w/4*3-overlay_w/4*3:main_h/5*4-overlay_h[video]; \

	[video]split[video][back]; \

	[back]scale=640:360, setsar=1:1[back]; \
	[back]boxblur=luma_radius='min(h,w)/20':luma_power=1:chroma_radius='min(cw,ch)/20':chroma_power=1[back]; \
	color=color=black@.5:size=640x360:d=1[coloroverlay]; \
	[back][coloroverlay]overlay[back]; \
	[back][video]overlay=0:56[video]; \

	[text][video]overlay=0:56 \
" \
-ss 0 \
-t 20 \
-c:v libx264 \
-preset fast \
-crf 24 \
-threads 0 \
-strict normal \
-y \
/Users/ugputu/Work/dsda/gpi_v2//upload/ready/1550262112_83_78.mp4