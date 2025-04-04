#!/usr/bin/env python3
"""
Environment check script for Garden Sensors project.
Verifies Python version and required dependencies.
"""

import sys
import os
import pkg_resources
import json

def check_python_version():
    """Check if Python version meets requirements."""
    required_version = (3, 7)
    current_version = sys.version_info[:2]
    if current_version < required_version:
        print(f"Error: Python {required_version[0]}.{required_version[1]} or higher is required")
        return False
    return True

def check_required_packages():
    """Check if all required packages are installed."""
    required_packages = {
        'pandas': '1.3.0',
        'numpy': '1.20.0',
        'matplotlib': '3.4.0',
        'requests': '2.26.0',
        'mysql-connector-python': '8.0.0',
        'python-dotenv': '0.19.0',
        'tenacity': '8.0.0',
        'ftplib': None  # Built-in
    }
    
    optional_packages = {
        'RPi.GPIO': '0.7.0'  # Only needed on Raspberry Pi
    }
    
    missing_packages = []
    for package, version in required_packages.items():
        try:
            if version:
                pkg_resources.require(f"{package}>={version}")
        except (pkg_resources.DistributionNotFound, pkg_resources.VersionConflict):
            missing_packages.append(f"{package}>={version}" if version else package)
    
    if missing_packages:
        print("Error: Missing required packages:")
        for package in missing_packages:
            print(f"  - {package}")
        return False
    
    # Check optional packages but don't fail if they're missing
    for package, version in optional_packages.items():
        try:
            if version:
                pkg_resources.require(f"{package}>={version}")
        except (pkg_resources.DistributionNotFound, pkg_resources.VersionConflict):
            print(f"Warning: Optional package not installed: {package}>={version}")
    
    return True

def check_file_permissions():
    """Check if required files and directories are accessible."""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    required_files = [
        'ProducePlot.py',
        'RunPump.py',
        'FTPConnectMod.py',
        'DBConnect.py'
    ]
    
    missing_files = []
    for file in required_files:
        file_path = os.path.join(script_dir, file)
        try:
            with open(file_path, 'r') as f:
                pass
        except (FileNotFoundError, PermissionError):
            missing_files.append(file)
    
    if missing_files:
        print("Error: Missing or inaccessible files:")
        for file in missing_files:
            print(f"  - {file}")
        return False
    return True

def main():
    """Main function to run all environment checks."""
    results = {
        'python_version': check_python_version(),
        'required_packages': check_required_packages(),
        'file_permissions': check_file_permissions()
    }
    
    # Print results as JSON for PHP to parse
    print(json.dumps(results))
    
    # Exit with status code 0 if all checks pass, 1 otherwise
    sys.exit(0 if all(results.values()) else 1)

if __name__ == '__main__':
    main() 