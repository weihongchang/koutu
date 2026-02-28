
import os
import sys
from rembg import remove
from PIL import Image

def remove_background(input_path, output_path):
    """
    使用rembg库移除图片背景
    """
    try:
        # 打开输入图片
        input_image = Image.open(input_path)
        
        # 移除背景
        output_image = remove(input_image)
        
        # 保存输出图片
        output_image.save(output_path)
        print(f"抠图完成！结果已保存至：{output_path}")
        
    except Exception as e:
        print(f"抠图过程中发生错误：{e}")
        sys.exit(1)

if __name__ == "__main__":
    # 检查是否提供了足够的命令行参数
    if len(sys.argv) > 3:
        print("使用方法: python removeBackground.py &lt;输入图片路径&gt; &lt;输出图片路径&gt;")
        sys.exit(1)
    
    # 从命令行参数获取输入输出路径
    input_image_path = sys.argv[1]
    output_image_path = sys.argv[2]

    # 检查输入文件是否存在
    if not os.path.exists(input_image_path):
        print(f"错误：输入文件 {input_image_path} 不存在！")
        sys.exit(1)
    
    # 执行抠图
    print(f"开始处理图片：{input_image_path}")
    remove_background(input_image_path, output_image_path)

