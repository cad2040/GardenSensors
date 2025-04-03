#!/bin/bash

# Create new directory structure
mkdir -p config/environment
mkdir -p docs/{setup,api,development}
mkdir -p public/{assets/{css,js,images},uploads}
mkdir -p src/{Controllers,Models,Services,Utils,Interfaces,Exceptions,Config}
mkdir -p tests/{Unit,Integration,Functional,Fixtures}
mkdir -p logs

# Create .gitkeep files to maintain empty directories
touch logs/.gitkeep
touch public/uploads/.gitkeep

# Move configuration files
mv config.php config/app.php
mv .env.example config/environment/
mv garden-sensors.conf config/

# Move documentation
mv SETUP.md docs/setup/installation.md
cp README.md docs/setup/

# Move GUI assets
if [ -d "GUI" ]; then
    mv GUI/* public/assets/
    rmdir GUI
fi

# Move source files
if [ -d "includes" ]; then
    mv includes/* src/
    rmdir includes
fi

# Move templates
if [ -d "templates" ]; then
    mv templates/* public/
    rmdir templates
fi

# Create new README.md
cat > README.md << 'EOL'
# Garden Sensors Project

## Overview
This project implements a garden monitoring system using various sensors and a web interface for data visualization and control.

## Quick Start
1. Clone the repository
2. Copy `config/environment/.env.example` to `config/environment/.env`
3. Run `composer install`
4. Run `python -m pip install -r requirements.txt`
5. Follow the setup instructions in `docs/setup/installation.md`

## Documentation
- [Installation Guide](docs/setup/installation.md)
- [API Documentation](docs/api/README.md)
- [Development Guide](docs/development/README.md)

## Project Structure
```
GardenSensors/
├── config/              # Configuration files
├── docs/               # Documentation
├── public/             # Public web files
├── src/               # Source code
├── tests/             # Test files
├── tools/             # Development tools
└── vendor/            # Dependencies
```

## Contributing
Please read our [Contributing Guide](docs/development/CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
EOL

echo "Project structure has been reorganized. Please review the changes and commit them to version control." 