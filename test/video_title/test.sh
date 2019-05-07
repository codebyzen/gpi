# ffmpeg \
# -timelimit 840 \
# -i /Users/ugputu/Work/dsda/gpi_v2//upload/temp/t_1550262112_86_65.jpg \
# -stream_loop 8 \
# -i /Users/ugputu/Work/dsda/gpi_v2//upload/temp/video123.mp4 \
# -i /Users/ugputu/Work/dsda/gpi_v2/assets/images/logo.png \
# -i /Users/ugputu/Work/dsda/gpi_v2//upload/temp/audio123.mp3 \
# -filter_complex \
# " \
# 	[0:v]scale=iw:ih[text]; \
# 	[1:v]scale=640:248[video]; \
# 	[2:v]scale=iw:ih[watermark]; \

# 	[video][watermark]overlay=main_w/4*3-overlay_w/4*3:main_h/5*4-overlay_h[video]; \

# 	[video]split[video][back]; \

# 	[back]scale=640:360, setsar=1:1[back]; \
# 	[back]boxblur=luma_radius='min(h,w)/20':luma_power=1:chroma_radius='min(cw,ch)/20':chroma_power=1[back]; \
# 	color=color=black@.5:size=640x360:d=1[coloroverlay]; \
# 	[back][coloroverlay]overlay[back]; \
# 	[back][video]overlay=0:56[video]; \

# 	[text][video]overlay=0:56 \
# " \
# -ss 0 \
# -t 20 \
# -c:v libx264 \
# -preset fast \
# -crf 24 \
# -threads 0 \
# -strict normal \
# -y \
# /Users/ugputu/Work/dsda/gpi_v2//upload/ready/1550262112_83_78.mp4


../../app/ffmpeg/mac/ffmpeg \
-progress ./progress.txt \
-timelimit 840 \
-i title.jpg \
-i video_1.mp4 \
-filter_complex \
" \
	[0:v]scale=640:768[back]; \
	[1:v]scale=640:360[video]; \
	[video]split[video][back]; \
	[back]scale=640:360, setsar=1:1[back]; \
	[back]boxblur=luma_radius='min(h,w)/20':luma_power=1:chroma_radius='min(cw,ch)/20':chroma_power=1[back]; \
	color=color=black@.5:size=640x360:d=1[coloroverlay]; \
	[back][coloroverlay]overlay[back]; \
	[back][video]overlay=0:0[video]; \
	color=color=black@.5:size=640x360:d=1[resultback]; \
	[resultback][video]overlay=0:0 \
" \
-ss 0 \
-t 59 \
-c:v libx264 \
-preset fast \
-crf 24 \
-c:a copy \
-threads 0 \
-strict normal \
-y \
./1556483967_32_31.mp4