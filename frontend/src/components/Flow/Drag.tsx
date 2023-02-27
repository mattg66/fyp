import React from 'react';

export default (props: {displayOnly: boolean}) => {
  const onDragStart = (event: any, nodeType: string) => {
    event.dataTransfer.setData('application/reactflow', nodeType);
    event.dataTransfer.effectAllowed = 'move';
  };

  return (
    <div>
      {props.displayOnly !== true &&
        <>
          <div className="description">You can drag these nodes to the pane on the right.</div>
          <div className="dndnode input" onDragStart={(event) => onDragStart(event, 'rackNode')} draggable>
            Rack Node
          </div>
          <div className="dndnode input" onDragStart={(event) => onDragStart(event, 'labelNode')} draggable>
            Label Node
          </div></>
      }
    </div>
  );
};