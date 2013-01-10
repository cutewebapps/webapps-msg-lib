<?php

class Msg_Message_StatusList 
{
    const PENDING = 1;
    const INPROGRESS = 2;
    const SENT = 4;
    const BOUNCED   = 8;
    const MUST_RESEND = 16;
}