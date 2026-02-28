import os
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

if __name__ == "__main__":
    # 设置输入输出路径
    input_image_path = "input.jpg"   # 输入图片
    output_image_path = "output.png" # 输出透明背景图片

    # 检查输入文件是否存在
    if not os.path.exists(input_image_path):
        print(f"错误：输入文件 {input_image_path} 不存在！")
    else:
        # 执行抠图
        print(f"开始处理图片：{input_image_path}")
        remove_background(input_image_path, output_image_path)
