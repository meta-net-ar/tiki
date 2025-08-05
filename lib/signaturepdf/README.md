# OS Requirements

This package requires the following tools to be installed on your system:
- **ImageMagick**: For image manipulation.
- **mkbitmap**: For creating bitmap images.
- **Potrace**: For converting bitmap images to vector graphics.
# Installation
To install the required tools, you can use the following commands based on your operating system:
## For Debian/Ubuntu:
```bash
sudo apt-get update
sudo apt-get install imagemagick potrace
```
## For macOS:
```bash
brew install imagemagick mkbitmap potrace
```
## For Windows:
You can download and install the tools from their respective websites or use a package manager like Chocolatey:
```bash
choco install imagemagick mkbitmap potrace
```

> **Note**:<br>
On some distributions, mkbitmap is included with potrace.
You can check with which mkbitmap after installing.