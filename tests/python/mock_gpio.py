"""Mock GPIO module for testing"""

# GPIO Modes
BOARD = 'BOARD'
BCM = 'BCM'
OUT = 'OUT'
IN = 'IN'
HIGH = 1
LOW = 0

# Mock GPIO state
_mode = None
_channel_mode = {}
_channel_state = {}

def setmode(mode):
    """Set the pin numbering mode"""
    global _mode
    _mode = mode

def setup(channel, mode, initial=None):
    """Set up a GPIO channel"""
    _channel_mode[channel] = mode
    if initial is not None:
        _channel_state[channel] = initial

def output(channel, state):
    """Set the output state of a GPIO channel"""
    if _channel_mode.get(channel) != OUT:
        raise RuntimeError(f"Channel {channel} not set up as output")
    _channel_state[channel] = state

def input(channel):
    """Read the state of a GPIO channel"""
    if _channel_mode.get(channel) != IN:
        raise RuntimeError(f"Channel {channel} not set up as input")
    return _channel_state.get(channel, LOW)

def cleanup():
    """Clean up GPIO channels"""
    global _mode
    _mode = None
    _channel_mode.clear()
    _channel_state.clear()

def setwarnings(flag):
    """Mock setting warnings"""
    pass 