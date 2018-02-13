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
        //bytes are stored in the DB as an 32bit INT type, the biggest number can only represent about 1.999GB
        if(bytes >= 2147483647)
        {
            return "> 2 GB";
        }
        return (bytes / 1073741824 ).toFixed(2) +" GB";
    }
}