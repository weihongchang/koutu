
import os
import sys
from rembg import remove
from PIL import Image

print("=== 测试 rembg 库 ===")
print("如果是首次运行，会自动下载模型文件，请耐心等待...")
print()

# 检查是否有测试图片
test_input = "input.jpg" if os.path.exists("input.jpg") else "input.png"

if os.path.exists(test_input):
    print(f"找到测试图片: {test_input}")
    test_output = "test_output.png"
    
    try:
        print(f"开始处理图片...")
        input_image = Image.open(test_input)
        output_image = remove(input_image)
        output_image.save(test_output)
        print(f"✓ 成功！输出文件: {test_output}")
        print()
        print("模型已下载完成，现在可以通过网页使用了！")
    except Exception as e:
        print(f"✗ 错误: {e}")
        import traceback
        traceback.print_exc()
else:
    print("未找到测试图片 (input.jpg 或 input.png)")
    print("但我们仍会尝试加载 rembg 来触发模型下载...")
    
    try:
        # 尝试导入和初始化 rembg，这会触发模型下载
        print("正在初始化 rembg...")
        from rembg import remove
        print("✓ rembg 初始化成功！模型已准备好。")
        print()
        print("现在可以通过网页使用了！")
    except Exception as e:
        print(f"✗ 错误: {e}")
        import traceback
        traceback.print_exc()

print()
input("按回车键退出...")

