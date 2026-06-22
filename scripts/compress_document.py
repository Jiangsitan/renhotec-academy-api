#!/usr/bin/env python3
"""
文档压缩工具
支持：PPT/PPTX → PDF 转换，PDF 压缩
依赖：PyMuPDF (fitz)
"""

import subprocess
import sys
import argparse
import shutil
import os
from pathlib import Path

try:
    import fitz  # PyMuPDF
except ImportError:
    print("ERROR: 请安装 PyMuPDF: pip install pymupdf", file=sys.stderr)
    sys.exit(1)


def find_soffice() -> str:
    """查找 LibreOffice 可执行文件"""
    # 优先使用 /work 目录下的 LibreOffice
    for name in ["/work/lib64/libreoffice/program/soffice", "/usr/lib64/libreoffice/program/soffice", "/usr/bin/soffice"]:
        if Path(name).exists():
            return name
    
    # 尝试使用 which 查找
    for name in ["soffice", "libreoffice"]:
        path = shutil.which(name)
        if path:
            return path
    
    return None


def ppt_to_pdf(pptx_path: str, output_dir: str) -> Path:
    """
    使用 LibreOffice 将 PPT 转换为 PDF
    
    Args:
        pptx_path: PPT 文件路径
        output_dir: 输出目录
    
    Returns:
        生成的 PDF 文件路径
    """
    soffice = find_soffice()
    if not soffice:
        print("ERROR: 未找到 LibreOffice，请确保已安装", file=sys.stderr)
        sys.exit(1)

    # 不设置 LD_LIBRARY_PATH，使用容器自身的系统库
    # LibreOffice 的图形库（libXinerama 等）在 /work/lib64 下
    # 系统核心库（glibc 等）使用容器内的

    pdf_file = Path(output_dir) / (Path(pptx_path).stem + ".pdf")

    try:
        cmd = [
            soffice, '--headless', '--norestore', '--nologo',
            '--convert-to', 'pdf', '--outdir', output_dir, pptx_path
        ]
        
        print(f"执行命令: {' '.join(cmd)}", file=sys.stderr)
        
        result = subprocess.run(
            cmd,
            check=True,
            timeout=60,
            capture_output=True,
            text=True
        )
        
        print(f"LibreOffice 输出: {result.stdout}", file=sys.stderr)
        if result.stderr:
            print(f"LibreOffice 错误: {result.stderr}", file=sys.stderr)
        
        if not pdf_file.exists():
            # 尝试查找生成的 PDF
            pdf_files = list(Path(output_dir).glob("*.pdf"))
            if pdf_files:
                pdf_file = pdf_files[0]
            else:
                print(f"ERROR: PDF 文件未生成", file=sys.stderr)
                sys.exit(1)
        
        return pdf_file
        
    except subprocess.TimeoutExpired:
        print("ERROR: PPT 转 PDF 超时", file=sys.stderr)
        sys.exit(1)
    except subprocess.CalledProcessError as e:
        print(f"ERROR: PPT 转 PDF 失败: {e}", file=sys.stderr)
        print(f"stdout: {e.stdout}", file=sys.stderr)
        print(f"stderr: {e.stderr}", file=sys.stderr)
        sys.exit(1)


def compress_pdf(pdf_path: Path, output_path: Path, quality: int = 85) -> Path:
    """
    保存 PDF 文件（不做图片压缩，只做格式优化）
    
    Args:
        pdf_path: 输入 PDF 路径
        output_path: 输出 PDF 路径
        quality: 图片质量 (1-100) - 未使用
    
    Returns:
        PDF 文件路径
    """
    try:
        doc = fitz.open(str(pdf_path))
        
        # 只做 PDF 优化保存，不做图片压缩
        doc.save(
            str(output_path),
            deflate=True,
            garbage=4,
            clean=True
        )
        doc.close()
        
        return output_path
        
    except Exception as e:
        print(f"ERROR: PDF 处理失败: {e}", file=sys.stderr)
        # 处理失败，直接复制原文件
        shutil.copy2(pdf_path, output_path)
        return output_path


def main():
    parser = argparse.ArgumentParser(description="文档压缩工具（PPT→PDF、PDF压缩）")
    parser.add_argument("input", help="输入文件路径（PPT/PPTX/PDF）")
    parser.add_argument("output", help="输出文件路径（PDF）")
    parser.add_argument("--quality", type=int, default=85, help="图片质量 1-100（默认85）")
    
    args = parser.parse_args()
    
    input_path = Path(args.input)
    output_path = Path(args.output)
    
    if not input_path.exists():
        print(f"ERROR: 文件不存在: {input_path}", file=sys.stderr)
        sys.exit(1)
    
    # 确保输出目录存在
    output_path.parent.mkdir(parents=True, exist_ok=True)
    
    ext = input_path.suffix.lower()
    
    if ext in ['.ppt', '.pptx']:
        # PPT → PDF
        print(f"正在转换 PPT 为 PDF...", file=sys.stderr)
        temp_dir = output_path.parent / "temp"
        temp_dir.mkdir(exist_ok=True)
        
        pdf_path = ppt_to_pdf(str(input_path), str(temp_dir))
        
        # 压缩 PDF
        print(f"正在压缩 PDF...", file=sys.stderr)
        final_path = compress_pdf(pdf_path, output_path, args.quality)
        
        # 清理临时文件
        if pdf_path != final_path and pdf_path.exists():
            pdf_path.unlink()
        if temp_dir.exists():
            try:
                temp_dir.rmdir()
            except:
                pass
        
        print(f"完成: {final_path}", file=sys.stderr)
        print(str(final_path))
        
    elif ext == '.pdf':
        # PDF 压缩
        print(f"正在压缩 PDF...", file=sys.stderr)
        final_path = compress_pdf(input_path, output_path, args.quality)
        
        print(f"完成: {final_path}", file=sys.stderr)
        print(str(final_path))
        
    else:
        print(f"ERROR: 不支持的文件格式: {ext}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
