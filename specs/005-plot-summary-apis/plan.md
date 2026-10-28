# Implementation Plan: 005-plot-summary-apis

**Branch**: `005-plot-summary-apis` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Stabilize the dashboard contract: PHP APIs call Python plot generation reliably, expose summary JSON for cards, and seed deterministic plant metrics for local validation.

## Technical Context

**Language/Version**: PHP, Python 3  
**Primary Dependencies**: Bokeh / plot scripts (`python/ProducePlot.py`, `generate_plot_api.py`), `PythonService`  
**Storage**: MySQL readings + views as needed  
**Testing**: `tests/python/test_produce_plot.py`, `test_gui_pages.py`, deploy seed SQL  
**Target Platform**: Local Apache or containerized PHP with Python sidecar/host tools  
**Constraints**: Prefer robust parsing of Python output over brittle stdout scraping

## Project Structure

```text
public/api/plot.php
public/api/summary.php
python/ProducePlot.py
python/generate_plot_api.py
src/Services/PythonService.php
database/seed_test_plot_data.sql
tests/python/
```

**Structure Decision**: Keep plot generation in Python; HTTP JSON façades in `public/api/`.
