function friendlyFilesize(bytes)
{
    if(bytes < 1024)
    {
        return bytes +" bytes";
    }
    if(bytes < 1048576)
    {
        return Math.round(bytes / 1024) +" KB";
    }
    if(bytes < 1073741824)
    {
        return (bytes / 1048576 ).toFixed(2) +" MB";
    }
    else
    {
        return (bytes / 1073741824 ).toFixed(2) +" GB";
    }
}