function getXMLHttpRequest() 
{
    var object = null;
    
    if (window.XMLHttpRequest) 
    {
        object = new XMLHttpRequest();
    } 
    else if (window.ActiveXObject) 
    {
        try
        {
            object = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch(e)
        {
        }
        
        if (object == null)
        {
            try
            {
                object = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch(e)
            {
            }
        }
    }
    
    if (object == null)
    {
        alert("Your browser is not supported");
    }
    
    return object;
}