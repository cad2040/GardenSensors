import os
import re
import socket
import subprocess
import time
from contextlib import closing

import pytest
from playwright._impl._errors import TargetClosedError
from playwright.sync_api import expect, sync_playwright


BASE_URL = os.getenv("GUI_BASE_URL", "http://127.0.0.1:8000")

PUBLIC_ROUTES = [
    ("/login.php", re.compile(r"Login", re.I)),
    ("/register.php", re.compile(r"Register", re.I)),
]

APP_ROUTES = [
    ("/", re.compile(r"Dashboard", re.I)),
    ("/index.php", re.compile(r"Dashboard", re.I)),
    ("/sensors.php", re.compile(r"Sensors", re.I)),
    ("/plants.php", re.compile(r"Plants", re.I)),
    ("/readings.php", re.compile(r"Sensor Readings|Readings", re.I)),
    ("/settings.php", re.compile(r"Settings", re.I)),
    ("/add_plant.php", re.compile(r"Add New Plant|Add Plant", re.I)),
    ("/add_sensor.php", re.compile(r"Add New Sensor|Add Sensor", re.I)),
]


def _wait_for_port(host: str, port: int, timeout: float = 10.0) -> None:
    start = time.time()
    while time.time() - start < timeout:
        with closing(socket.socket(socket.AF_INET, socket.SOCK_STREAM)) as sock:
            if sock.connect_ex((host, port)) == 0:
                return
        time.sleep(0.1)
    raise RuntimeError(f"Timed out waiting for {host}:{port}")


@pytest.fixture(scope="session")
def gui_server():
    if os.getenv("GUI_BASE_URL"):
        yield None
        return

    repo_root = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
    public_dir = os.path.join(repo_root, "public")
    log_file = open("/tmp/garden-sensors-gui.log", "w", encoding="utf-8")
    proc = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8000", "-t", public_dir],
        cwd=repo_root,
        stdout=log_file,
        stderr=subprocess.STDOUT,
    )
    _wait_for_port("127.0.0.1", 8000)
    try:
        yield proc
    finally:
        proc.terminate()
        try:
            proc.wait(timeout=5)
        except subprocess.TimeoutExpired:
            proc.kill()
        log_file.close()


@pytest.fixture()
def page(gui_server):
    with sync_playwright() as p:
        try:
            browser = p.chromium.launch()
        except TargetClosedError as exc:
            message = str(exc)
            if "error while loading shared libraries" in message:
                pytest.skip(
                    "Skipping GUI tests: missing system libraries for Playwright browser runtime."
                )
            raise
        context = browser.new_context(base_url=BASE_URL)
        page = context.new_page()
        yield page
        context.close()
        browser.close()


def _is_login_page(page) -> bool:
    return page.locator("#username").count() > 0 and page.locator("#password").count() > 0


@pytest.mark.parametrize("path,title_regex", PUBLIC_ROUTES)
def test_public_pages_render(page, path, title_regex):
    response = page.goto(path, wait_until="domcontentloaded")
    assert response is not None
    assert response.status < 500
    expect(page).to_have_title(title_regex)
    expect(page.locator("form").first).to_be_visible()


@pytest.mark.parametrize("path,heading_regex", APP_ROUTES)
def test_whole_site_routes_accessible(page, path, heading_regex):
    response = page.goto(path, wait_until="domcontentloaded")
    assert response is not None
    assert response.status < 500

    if _is_login_page(page):
        expect(page).to_have_url(re.compile(r".*login\.php", re.I))
        expect(page.locator("#username")).to_be_visible()
        return

    expect(page.locator("h1, h2").filter(has_text=heading_regex).first).to_be_visible()


@pytest.mark.skipif(
    not os.getenv("GUI_TEST_USERNAME") or not os.getenv("GUI_TEST_PASSWORD"),
    reason="Set GUI_TEST_USERNAME and GUI_TEST_PASSWORD to run authenticated GUI coverage.",
)
def test_authenticated_navigation_across_site(page):
    page.goto("/login.php", wait_until="domcontentloaded")
    page.fill("#username", os.environ["GUI_TEST_USERNAME"])
    page.fill("#password", os.environ["GUI_TEST_PASSWORD"])
    page.locator("button[type='submit']").click()

    expect(page).to_have_url(re.compile(r".*(index\.php)?/?$", re.I))

    for path, heading_regex in APP_ROUTES:
        page.goto(path, wait_until="domcontentloaded")
        expect(page.locator("h1, h2").filter(has_text=heading_regex).first).to_be_visible()
