
from flask import Flask, request, send_file, jsonify
from rembg import remove
from PIL import Image
import io
import os
from werkzeug.utils import secure_filename

app = Flask(__name__)

# 配置允许的文件扩展名
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@app.route('/')
def index():
    return send_file('index.html')

@app.route('/upload', methods=['POST'])
def upload_file():
    if 'file' not in request.files:
        return jsonify({'error': '没有文件部分'}), 400
    
    file = request.files['file']
    if file.filename == '':
        return jsonify({'error': '没有选择文件'}), 400
    
    if file and allowed_file(file.filename):
        try:
            # 读取上传的图片
            input_image = Image.open(file.stream)
            
            # 移除背景
            output_image = remove(input_image)
            
            # 保存处理后的图片到内存
            img_io = io.BytesIO()
            output_image.save(img_io, 'PNG')
            img_io.seek(0)
            
            return send_file(
                img_io,
                mimetype='image/png',
                as_attachment=True,
                download_name='result.png'
            )
        except Exception as e:
            return jsonify({'error': f'处理图片时出错: {str(e)}'}), 500
    
    return jsonify({'error': '不允许的文件类型'}), 400

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
