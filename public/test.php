<?php header('Content-type: Application/json'); ?>
{"layout":"s","hidpiRatio":1,"charts":[{"panes":[{"leftAxis":{"content":"data:,","contentWidth":0,"contentHeight":478},"rightAxis":{"content":"data:image/png;base64,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 more
